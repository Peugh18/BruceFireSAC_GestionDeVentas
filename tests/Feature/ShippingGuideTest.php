<?php

use App\Jobs\SendShippingGuideJob;
use App\Models\ShippingGuide;
use App\Models\ShippingGuideItem;
use App\Models\User;
use App\Services\Billing\Data\SunatSendResult;
use App\Services\Billing\GreenterService;
use App\Services\Shipping\ShippingService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

function shippingUser(array $permissions = ['shipping_guides.view', 'shipping_guides.create']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

function basePayload(array $overrides = []): array
{
    return array_merge([
        'sale_id' => null,
        'motivo_traslado' => 'traslado_entre_establecimientos',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'origen' => 'Av. Principal 123, Lima',
        'destino' => 'Jr. Los Alamos 456, Lima',
        'destinatario_nombre' => 'Cliente de Prueba',
        'destinatario_documento' => '12345678',
        'peso_total' => 25.5,
        'observaciones' => null,
        'items' => [
            ['descripcion' => 'Extintor PQS ABC 6kg', 'cantidad' => 5, 'unidad' => 'NIU', 'peso' => 5],
        ],
    ], $overrides);
}

test('shipping routes redirect guests to login', function () {
    $this->get(route('shipping.index'))->assertRedirect(route('login'));
    $this->get(route('shipping.create'))->assertRedirect(route('login'));
    $this->post(route('shipping.store'), [])->assertRedirect(route('login'));
});

test('user without shipping_guides permissions is forbidden', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('shipping.index'))->assertForbidden();
    $this->actingAs($user)->get(route('shipping.create'))->assertForbidden();
});

test('user with only shipping_guides.view cannot create', function () {
    $user = shippingUser(['shipping_guides.view']);

    $this->actingAs($user)
        ->post(route('shipping.store'), basePayload(['modalidad' => 'transporte_privado', 'vehiculo_placa' => 'ABC-123', 'conductor_nombre' => 'Juan Perez', 'conductor_licencia' => 'Q12345678']))
        ->assertForbidden();
});

test('creating a guide with private transport requires vehicle and driver', function () {
    Queue::fake();
    $user = shippingUser();

    $payload = basePayload([
        'modalidad' => 'transporte_privado',
        'vehiculo_placa' => 'ABC-123',
        'conductor_nombre' => 'Juan Perez',
        'conductor_licencia' => 'Q12345678',
    ]);

    $this->actingAs($user)->post(route('shipping.store'), $payload)->assertRedirect(route('shipping.index'));

    $this->assertDatabaseHas('shipping_guides', [
        'modalidad' => 'transporte_privado',
        'vehiculo_placa' => 'ABC-123',
        'conductor_nombre' => 'Juan Perez',
        'serie' => 'T001',
        'correlativo' => '00000001',
        'estado' => 'pendiente',
    ]);

    $guide = ShippingGuide::firstOrFail();
    $this->assertDatabaseHas('shipping_guide_items', [
        'shipping_guide_id' => $guide->id,
        'descripcion' => 'Extintor PQS ABC 6kg',
    ]);

    Queue::assertPushed(SendShippingGuideJob::class, fn ($job) => $job->guide->id === $guide->id);
});

test('creating a guide with public transport requires transportista data', function () {
    Queue::fake();
    $user = shippingUser();

    $payload = basePayload([
        'modalidad' => 'transporte_publico',
        'transportista_razon_social' => 'Transportes Rapidos S.A.C.',
        'transportista_ruc' => '20123456789',
    ]);

    $this->actingAs($user)->post(route('shipping.store'), $payload)->assertRedirect(route('shipping.index'));

    $this->assertDatabaseHas('shipping_guides', [
        'modalidad' => 'transporte_publico',
        'transportista_razon_social' => 'Transportes Rapidos S.A.C.',
        'transportista_ruc' => '20123456789',
        'vehiculo_placa' => null,
    ]);
});

test('private transport without vehicle or driver fails validation', function () {
    $user = shippingUser();

    $payload = basePayload(['modalidad' => 'transporte_privado']);

    $this->actingAs($user)
        ->post(route('shipping.store'), $payload)
        ->assertSessionHasErrors(['vehiculo_placa', 'conductor_nombre', 'conductor_licencia']);

    $this->assertDatabaseCount('shipping_guides', 0);
});

test('public transport without transportista data fails validation', function () {
    $user = shippingUser();

    $payload = basePayload(['modalidad' => 'transporte_publico']);

    $this->actingAs($user)
        ->post(route('shipping.store'), $payload)
        ->assertSessionHasErrors(['transportista_razon_social', 'transportista_ruc']);

    $this->assertDatabaseCount('shipping_guides', 0);
});

test('sending a guide that sunat accepts stores xml/cdr and marks it aceptado', function () {
    Storage::fake('local');

    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('sendDespatch')->once()->andReturn(new SunatSendResult(
            success: true,
            xml: '<Despatch>signed</Despatch>',
            cdrZip: 'binary-zip-content',
            code: '0',
            description: 'La Guia de Remision ha sido aceptada',
        ));
    });

    $guide = ShippingGuide::factory()
        ->has(ShippingGuideItem::factory()->count(2), 'items')
        ->create(['estado' => 'pendiente', 'intentos' => 0]);

    app(ShippingService::class)->send($guide);

    $guide->refresh();

    expect($guide->estado)->toBe('aceptado');
    expect($guide->intentos)->toBe(1);
    expect($guide->respuesta_sunat)->toContain('aceptada');
    expect($guide->error)->toBeNull();
    expect($guide->hash)->not->toBeNull();

    Storage::disk('local')->assertExists($guide->xml_path);
    Storage::disk('local')->assertExists($guide->cdr_path);
});

test('sending a guide that fails marks it as error', function () {
    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('sendDespatch')->once()->andReturn(
            SunatSendResult::failed('No se pudo conectar con el servicio de SUNAT.')
        );
    });

    $guide = ShippingGuide::factory()
        ->has(ShippingGuideItem::factory()->count(1), 'items')
        ->create(['estado' => 'pendiente', 'intentos' => 0]);

    app(ShippingService::class)->send($guide);

    $guide->refresh();

    expect($guide->estado)->toBe('error');
    expect($guide->intentos)->toBe(1);
    expect($guide->error)->toContain('SUNAT');
});

test('sending a guide sunat rejects marks it as rechazado', function () {
    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('sendDespatch')->once()->andReturn(new SunatSendResult(
            success: true,
            xml: '<Despatch>signed</Despatch>',
            cdrZip: 'binary-zip-content',
            code: '2800',
            description: 'La guia ha sido rechazada',
        ));
    });

    $guide = ShippingGuide::factory()
        ->has(ShippingGuideItem::factory()->count(1), 'items')
        ->create(['estado' => 'pendiente', 'intentos' => 0]);

    app(ShippingService::class)->send($guide);

    expect($guide->fresh()->estado)->toBe('rechazado');
});

test('the queued job delegates to the shipping service', function () {
    Storage::fake('local');

    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('sendDespatch')->once()->andReturn(new SunatSendResult(
            success: true,
            xml: '<Despatch>signed</Despatch>',
            cdrZip: 'binary-zip-content',
            code: '0',
            description: 'Aceptada',
        ));
    });

    $guide = ShippingGuide::factory()
        ->has(ShippingGuideItem::factory()->count(1), 'items')
        ->create(['estado' => 'pendiente']);

    (new SendShippingGuideJob($guide))->handle(app(ShippingService::class));

    expect($guide->fresh()->estado)->toBe('aceptado');
});
