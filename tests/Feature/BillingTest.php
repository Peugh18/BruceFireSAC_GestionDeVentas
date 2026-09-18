<?php

use App\Jobs\SendElectronicDocumentJob;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\ElectronicDocument;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\Billing\BillingService;
use App\Services\Billing\Data\SunatSendResult;
use App\Services\Billing\GreenterService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

function billingUser(array $permissions = ['billing.view', 'billing.issue', 'billing.retry']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

function saleWithItems(array $saleAttributes = []): Sale
{
    $sale = Sale::factory()->create($saleAttributes);

    $catalogItem = CatalogItem::factory()->create();
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'catalog_item_id' => $catalogItem->id,
        'cantidad' => 2,
        'precio_unitario' => 100,
        'descuento' => 0,
        'subtotal' => 200,
    ]);

    return $sale->fresh();
}

test('billing issue route redirects guests to login', function () {
    $sale = Sale::factory()->create();

    $this->post(route('billing.issue', $sale->id))->assertRedirect(route('login'));
});

test('user without billing.issue permission is forbidden', function () {
    $user = User::factory()->create();
    $sale = Sale::factory()->create();

    $this->actingAs($user)
        ->post(route('billing.issue', $sale->id))
        ->assertForbidden();
});

test('issuing a comprobante for a ruc client creates a factura and queues the send job', function () {
    Queue::fake();

    $user = billingUser();
    $client = Client::factory()->create(['tipo_documento' => 'ruc']);
    $sale = Sale::factory()->create(['client_id' => $client->id]);

    $this->actingAs($user)
        ->post(route('billing.issue', $sale->id))
        ->assertRedirect();

    $this->assertDatabaseHas('electronic_documents', [
        'sale_id' => $sale->id,
        'tipo' => 'factura',
        'serie' => 'F001',
        'correlativo' => '00000001',
        'estado' => 'pendiente',
    ]);

    Queue::assertPushed(SendElectronicDocumentJob::class, fn ($job) => $job->document->sale_id === $sale->id);
});

test('issuing a comprobante for a dni client creates a boleta', function () {
    Queue::fake();

    $user = billingUser();
    $client = Client::factory()->create(['tipo_documento' => 'dni']);
    $sale = Sale::factory()->create(['client_id' => $client->id]);

    $this->actingAs($user)->post(route('billing.issue', $sale->id))->assertRedirect();

    $this->assertDatabaseHas('electronic_documents', [
        'sale_id' => $sale->id,
        'tipo' => 'boleta',
        'serie' => 'B001',
    ]);
});

test('issuing twice reuses the same electronic document', function () {
    Queue::fake();

    $user = billingUser();
    $sale = Sale::factory()->create();

    $this->actingAs($user)->post(route('billing.issue', $sale->id));
    $this->actingAs($user)->post(route('billing.issue', $sale->id));

    expect(ElectronicDocument::where('sale_id', $sale->id)->count())->toBe(1);
    Queue::assertPushed(SendElectronicDocumentJob::class, 2);
});

test('cannot issue again once the document is already aceptado', function () {
    Queue::fake();

    $user = billingUser();
    $sale = Sale::factory()->create();
    ElectronicDocument::factory()->aceptado()->create(['sale_id' => $sale->id]);

    $this->actingAs($user)
        ->post(route('billing.issue', $sale->id))
        ->assertSessionHasErrors('estado');

    Queue::assertNothingPushed();
});

test('retry route redirects guests to login', function () {
    $document = ElectronicDocument::factory()->error()->create();

    $this->post(route('billing.retry', $document->id))->assertRedirect(route('login'));
});

test('user without billing.retry permission is forbidden', function () {
    $user = billingUser(['billing.view', 'billing.issue']);
    $document = ElectronicDocument::factory()->error()->create();

    $this->actingAs($user)
        ->post(route('billing.retry', $document->id))
        ->assertForbidden();
});

test('retry requeues the send job only when the document is in error', function () {
    Queue::fake();

    $user = billingUser();
    $document = ElectronicDocument::factory()->error()->create();

    $this->actingAs($user)
        ->post(route('billing.retry', $document->id))
        ->assertRedirect();

    Queue::assertPushed(SendElectronicDocumentJob::class, fn ($job) => $job->document->id === $document->id);
});

test('retry is rejected when the document is not in error', function () {
    Queue::fake();

    $user = billingUser();
    $document = ElectronicDocument::factory()->create(['estado' => 'pendiente']);

    $this->actingAs($user)
        ->post(route('billing.retry', $document->id))
        ->assertSessionHasErrors('estado');

    Queue::assertNothingPushed();
});

test('sending a document that sunat accepts stores the xml/cdr and marks it aceptado', function () {
    Storage::fake('local');

    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('send')->once()->andReturn(new SunatSendResult(
            success: true,
            xml: '<Invoice>signed</Invoice>',
            cdrZip: 'binary-zip-content',
            code: '0',
            description: 'La Factura numero F001-1, ha sido aceptada',
        ));
    });

    $sale = saleWithItems(['client_id' => Client::factory()->create(['tipo_documento' => 'ruc'])->id]);
    $document = ElectronicDocument::factory()->create([
        'sale_id' => $sale->id,
        'tipo' => 'factura',
        'estado' => 'pendiente',
        'intentos' => 0,
    ]);

    app(BillingService::class)->send($document);

    $document->refresh();

    expect($document->estado)->toBe('aceptado');
    expect($document->intentos)->toBe(1);
    expect($document->respuesta_sunat)->toContain('aceptada');
    expect($document->error)->toBeNull();
    expect($document->hash)->not->toBeNull();
    expect($document->xml_path)->not->toBeNull();
    expect($document->cdr_path)->not->toBeNull();

    Storage::disk('local')->assertExists($document->xml_path);
    Storage::disk('local')->assertExists($document->cdr_path);
});

test('sending a document that fails marks it as error and keeps it retryable', function () {
    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('send')->once()->andReturn(
            SunatSendResult::failed('No se pudo conectar con el servicio de SUNAT.')
        );
    });

    $sale = saleWithItems();
    $document = ElectronicDocument::factory()->create([
        'sale_id' => $sale->id,
        'estado' => 'pendiente',
        'intentos' => 0,
    ]);

    app(BillingService::class)->send($document);

    $document->refresh();

    expect($document->estado)->toBe('error');
    expect($document->intentos)->toBe(1);
    expect($document->error)->toContain('SUNAT');
});

test('sending a document sunat rejects marks it as rechazado', function () {
    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('send')->once()->andReturn(new SunatSendResult(
            success: true,
            xml: '<Invoice>signed</Invoice>',
            cdrZip: 'binary-zip-content',
            code: '2800',
            description: 'El comprobante ha sido rechazado',
        ));
    });

    $sale = saleWithItems();
    $document = ElectronicDocument::factory()->create([
        'sale_id' => $sale->id,
        'estado' => 'pendiente',
        'intentos' => 0,
    ]);

    app(BillingService::class)->send($document);

    expect($document->fresh()->estado)->toBe('rechazado');
});

test('the queued job delegates to the billing service', function () {
    Storage::fake('local');

    $this->mock(GreenterService::class, function ($mock) {
        $mock->shouldReceive('send')->once()->andReturn(new SunatSendResult(
            success: true,
            xml: '<Invoice>signed</Invoice>',
            cdrZip: 'binary-zip-content',
            code: '0',
            description: 'Aceptada',
        ));
    });

    $sale = saleWithItems();
    $document = ElectronicDocument::factory()->create(['sale_id' => $sale->id, 'estado' => 'pendiente']);

    (new SendElectronicDocumentJob($document))->handle(app(BillingService::class));

    expect($document->fresh()->estado)->toBe('aceptado');
});
