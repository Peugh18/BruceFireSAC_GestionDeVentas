<?php

use App\Models\Certificate;
use App\Models\Equipment;
use App\Models\ServiceOrder;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function certificateUser(array $permissions = ['certificates.view', 'certificates.issue', 'certificates.manage', 'service_orders.view']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('certificate routes redirect guests to login', function () {
    $this->get('/certificates')->assertRedirect(route('login'));
    $this->post('/certificates')->assertRedirect(route('login'));
});

test('certificate routes forbid users without permissions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('certificates.index'))
        ->assertForbidden();
});

test('user can generate a certificate covering every equipment in the order', function () {
    $user = certificateUser();
    $order = ServiceOrder::factory()->create();
    $equipment = Equipment::factory()->count(5)->create();
    $order->equipment()->attach($equipment->pluck('id'));

    $payload = [
        'service_order_id' => $order->id,
        'tipo' => 'operatividad_garantia',
        'fecha_emision' => now()->toDateString(),
        'fecha_vigencia' => now()->addYear()->toDateString(),
        'observaciones' => 'Certificado de prueba',
        'equipment_ids' => $equipment->pluck('id')->all(),
    ];

    $response = $this->actingAs($user)->post(route('certificates.store'), $payload);

    $certificate = Certificate::where('service_order_id', $order->id)->firstOrFail();

    $response->assertRedirect(route('certificates.show', $certificate->id));

    expect($certificate->numero)->toStartWith('CERT-');
    expect($certificate->token)->not->toBeEmpty();
    expect($certificate->estado)->toBe('vigente');
    expect($certificate->items()->count())->toBe(5);

    foreach ($equipment as $item) {
        $this->assertDatabaseHas('certificate_items', [
            'certificate_id' => $certificate->id,
            'equipment_id' => $item->id,
        ]);
    }
});

test('certificate detail page exposes a qr code pointing to the public verification url', function () {
    $user = certificateUser();
    $order = ServiceOrder::factory()->create();
    $equipment = Equipment::factory()->create();
    $order->equipment()->attach($equipment->id);

    $certificate = Certificate::factory()->create(['service_order_id' => $order->id]);
    $certificate->items()->create(['equipment_id' => $equipment->id]);

    $this->actingAs($user)
        ->get(route('certificates.show', $certificate->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('certificates/show')
            ->where('certificate.id', $certificate->id)
            ->where('verificationUrl', route('certificates.verify', $certificate->token))
            ->has('qrSvg')
        );
});

test('public verification page shows certificate details by token without authentication', function () {
    $order = ServiceOrder::factory()->create();
    $equipment = Equipment::factory()->create();
    $order->equipment()->attach($equipment->id);

    $certificate = Certificate::factory()->create(['service_order_id' => $order->id]);
    $certificate->items()->create(['equipment_id' => $equipment->id]);

    $this->get(route('certificates.verify', $certificate->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('certificates/verify')
            ->where('certificate.numero', $certificate->numero)
            ->where('certificate.estado_key', 'vigente')
            ->where('certificate.es_vigente', true)
            ->has('certificate.equipos', 1)
        );
});

test('public verification page returns 404 for an unknown token', function () {
    $this->get(route('certificates.verify', 'token-inexistente'))->assertNotFound();
});

test('certificate status transitions follow the allowed state machine', function () {
    $user = certificateUser();
    $certificate = Certificate::factory()->create(['estado' => 'vigente']);

    $this->actingAs($user)
        ->patch(route('certificates.status', $certificate->id), ['estado' => 'anulado'])
        ->assertRedirect();

    expect($certificate->fresh()->estado)->toBe('anulado');

    $this->actingAs($user)
        ->patch(route('certificates.status', $certificate->id), ['estado' => 'vigente'])
        ->assertSessionHasErrors('estado');
});

test('anulado certificate is not vigente on the public verification page', function () {
    $certificate = Certificate::factory()->create(['estado' => 'anulado']);

    $this->get(route('certificates.verify', $certificate->token))
        ->assertInertia(fn (Assert $page) => $page
            ->where('certificate.estado_key', 'anulado')
            ->where('certificate.es_vigente', false)
        );
});
