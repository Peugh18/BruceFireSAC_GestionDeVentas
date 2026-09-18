<?php

use App\Jobs\SendElectronicDocumentJob;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\ClientSite;
use App\Models\ElectronicDocument;
use App\Models\Equipment;
use App\Models\InventoryStock;
use App\Models\InventoryUnit;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function saleUser(array $permissions = ['sales.view', 'sales.create', 'sales.scan_units']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('sale routes redirect guests to login', function (string $method, string $path) {
    $this->{$method}($path)->assertRedirect(route('login'));
})->with([
    ['get', '/sales'],
    ['get', '/sales/create'],
    ['post', '/sales'],
    ['get', '/sales/1'],
]);

test('sale routes forbid users without permissions', function () {
    $user = User::factory()->create();
    $sale = Sale::factory()->create();

    $this->actingAs($user)->get('/sales')->assertForbidden();
    $this->actingAs($user)->get('/sales/create')->assertForbidden();
    $this->actingAs($user)->get("/sales/{$sale->id}")->assertForbidden();
});

test('user with permission can view sales list', function () {
    $user = saleUser();
    $sale = Sale::factory()->create();

    $this->actingAs($user)
        ->get(route('sales.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/index')
            ->has('sales.data', 1)
            ->where('sales.data.0.id', $sale->id)
        );
});

test('user can create direct sale with contado payments', function () {
    $user = saleUser();
    $client = Client::factory()->create();
    $catalogItem = CatalogItem::factory()->create(['precio' => 150.00, 'controla_stock' => false]);

    $payload = [
        'client_id' => $client->id,
        'tipo_comprobante' => 'boleta',
        'fecha' => now()->toDateString(),
        'condicion_pago' => 'contado',
        'observaciones' => 'Venta en efectivo',
        'items' => [
            [
                'catalog_item_id' => $catalogItem->id,
                'cantidad' => 2,
                'precio_unitario' => 150.00,
                'descuento' => 0,
            ],
        ],
        'payments' => [
            [
                'forma_pago' => 'efectivo',
                'monto' => 354.00,
                'referencia' => 'Efectivo en caja',
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('sales.store'), $payload)
        ->assertRedirect();

    $this->assertDatabaseHas('sales', [
        'client_id' => $client->id,
        'vendedor_user_id' => $user->id,
        'condicion_pago' => 'contado',
        'subtotal' => 300.00,
        'igv' => 54.00,
        'total' => 354.00,
        'estado' => 'completada',
    ]);

    $sale = Sale::where('client_id', $client->id)->first();

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'catalog_item_id' => $catalogItem->id,
        'cantidad' => 2,
        'precio_unitario' => 150.00,
        'subtotal' => 300.00,
    ]);

    $this->assertDatabaseHas('sale_payments', [
        'sale_id' => $sale->id,
        'forma_pago' => 'efectivo',
        'monto' => 354.00,
        'referencia' => 'Efectivo en caja',
    ]);
});

test('user can create direct sale with credito installments', function () {
    $user = saleUser();
    $client = Client::factory()->create();
    $catalogItem = CatalogItem::factory()->create(['precio' => 500.00, 'controla_stock' => false]);

    $payload = [
        'client_id' => $client->id,
        'tipo_comprobante' => 'boleta',
        'fecha' => now()->toDateString(),
        'condicion_pago' => 'credito',
        'observaciones' => 'Venta a 2 cuotas',
        'items' => [
            [
                'catalog_item_id' => $catalogItem->id,
                'cantidad' => 1,
                'precio_unitario' => 500.00,
                'descuento' => 0,
            ],
        ],
        'installments' => [
            [
                'numero_cuota' => 1,
                'monto' => 295.00,
                'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            ],
            [
                'numero_cuota' => 2,
                'monto' => 295.00,
                'fecha_vencimiento' => now()->addDays(60)->toDateString(),
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('sales.store'), $payload)
        ->assertRedirect();

    $sale = Sale::where('client_id', $client->id)->first();

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'condicion_pago' => 'credito',
        'total' => 590.00,
    ]);

    $this->assertDatabaseCount('sale_installments', 2);
    $this->assertDatabaseHas('sale_installments', [
        'sale_id' => $sale->id,
        'numero_cuota' => 1,
        'monto' => 295.00,
        'estado' => 'pendiente',
    ]);
});

test('creating a sale discounts stock controlled items', function () {
    $user = saleUser();
    $client = Client::factory()->create();
    $catalogItem = CatalogItem::factory()->create(['precio' => 50.00, 'controla_stock' => true]);
    $stock = InventoryStock::where('catalog_item_id', $catalogItem->id)->firstOrFail();
    $stock->update(['stock_actual' => 5]);

    $this->actingAs($user)
        ->post(route('sales.store'), [
            'client_id' => $client->id,
            'tipo_comprobante' => 'boleta',
            'fecha' => '2026-09-18',
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'catalog_item_id' => $catalogItem->id,
                    'cantidad' => 2,
                    'precio_unitario' => 50.00,
                    'descuento' => 0,
                ],
            ],
            'payments' => [
                [
                    'forma_pago' => 'efectivo',
                    'monto' => 118.00,
                    'referencia' => null,
                ],
            ],
        ])
        ->assertRedirect();

    expect($stock->fresh()->stock_actual)->toBe('3.000');

    $sale = Sale::where('client_id', $client->id)->firstOrFail();

    $this->assertDatabaseHas('inventory_movements', [
        'catalog_item_id' => $catalogItem->id,
        'tipo' => 'salida',
        'cantidad' => 2,
        'stock_antes' => 5,
        'stock_despues' => 3,
        'motivo' => 'venta',
        'referencia' => $sale->numero,
        'fecha' => '2026-09-18',
        'usuario_id' => $user->id,
    ]);
});

test('creating a serialized sale links the physical unit and creates client equipment', function () {
    Queue::fake();

    $user = saleUser();
    $client = Client::factory()->create(['tipo_documento' => 'ruc']);
    $site = ClientSite::factory()->for($client)->create();
    $catalogItem = CatalogItem::factory()->create([
        'nombre' => 'Extintor PQS ABC 6kg',
        'precio' => 180.00,
        'controla_stock' => true,
        'control_serializado' => true,
        'genera_barcode' => true,
    ]);
    $stock = InventoryStock::where('catalog_item_id', $catalogItem->id)->firstOrFail();
    $stock->update(['stock_actual' => 1]);
    $unit = InventoryUnit::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'serie' => 'SER-VENDIDA-001',
        'marca' => 'Amerex',
        'capacidad' => '6 kg',
        'anio' => 2025,
        'barcode' => 'EXT-UNIT-001',
    ]);

    $this->actingAs($user)
        ->post(route('sales.store'), [
            'client_id' => $client->id,
            'client_site_id' => $site->id,
            'tipo_comprobante' => 'factura',
            'fecha' => '2026-09-18',
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'catalog_item_id' => $catalogItem->id,
                    'inventory_unit_id' => $unit->id,
                    'cantidad' => 1,
                    'precio_unitario' => 180.00,
                    'descuento' => 0,
                ],
            ],
            'payments' => [
                [
                    'forma_pago' => 'efectivo',
                    'monto' => 212.40,
                    'referencia' => null,
                ],
            ],
        ])
        ->assertRedirect();

    $sale = Sale::where('client_id', $client->id)->firstOrFail();

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'catalog_item_id' => $catalogItem->id,
        'inventory_unit_id' => $unit->id,
        'cantidad' => 1,
    ]);
    $unit->refresh();

    expect($stock->fresh()->stock_actual)->toBe('0.000');
    expect($unit->en_stock)->toBeFalse();
    expect($unit->estado)->toBe('vendido');

    $this->assertDatabaseHas('equipment', [
        'client_id' => $client->id,
        'client_site_id' => $site->id,
        'vehicle_id' => null,
        'origen' => 'vendido_bruce_fire',
        'tipo_equipo' => 'Extintor PQS ABC 6kg',
        'marca' => 'Amerex',
        'capacidad' => '6 kg',
        'serie_fabricante' => 'SER-VENDIDA-001',
        'anio_fabricacion' => '2025',
        'barcode' => 'EXT-UNIT-001',
        'estado' => 'activo',
    ]);
    expect(Equipment::firstOrFail()->events()->where('tipo', 'alta')->exists())->toBeTrue();

    Queue::assertPushed(SendElectronicDocumentJob::class);
});

test('creating a sale automatically issues the correct electronic document type', function (string $documentType, string $expectedTipo) {
    Queue::fake();

    $user = saleUser();
    $client = Client::factory()->create(['tipo_documento' => $documentType]);
    $catalogItem = CatalogItem::factory()->create(['precio' => 100.00, 'controla_stock' => false]);

    $this->actingAs($user)
        ->post(route('sales.store'), [
            'client_id' => $client->id,
            'tipo_comprobante' => $expectedTipo,
            'fecha' => '2026-09-18',
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'catalog_item_id' => $catalogItem->id,
                    'cantidad' => 1,
                    'precio_unitario' => 100.00,
                    'descuento' => 0,
                ],
            ],
            'payments' => [
                [
                    'forma_pago' => 'efectivo',
                    'monto' => 118.00,
                    'referencia' => null,
                ],
            ],
        ])
        ->assertRedirect();

    $sale = Sale::where('client_id', $client->id)->firstOrFail();
    $document = ElectronicDocument::where('sale_id', $sale->id)->firstOrFail();

    expect($document->tipo)->toBe($expectedTipo);
    Queue::assertPushed(SendElectronicDocumentJob::class, fn (SendElectronicDocumentJob $job) => $job->document->is($document));
})->with([
    'ruc client gets factura' => ['ruc', 'factura'],
    'dni client gets boleta' => ['dni', 'boleta'],
]);

test('creating a sale fails and rolls back when stock is insufficient', function () {
    $user = saleUser();
    $client = Client::factory()->create();
    $catalogItem = CatalogItem::factory()->create(['precio' => 50.00, 'controla_stock' => true]);
    $stock = InventoryStock::where('catalog_item_id', $catalogItem->id)->firstOrFail();
    $stock->update(['stock_actual' => 1]);

    $this->actingAs($user)
        ->post(route('sales.store'), [
            'client_id' => $client->id,
            'tipo_comprobante' => 'boleta',
            'fecha' => '2026-09-18',
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'catalog_item_id' => $catalogItem->id,
                    'cantidad' => 2,
                    'precio_unitario' => 50.00,
                    'descuento' => 0,
                ],
            ],
            'payments' => [
                [
                    'forma_pago' => 'efectivo',
                    'monto' => 118.00,
                    'referencia' => null,
                ],
            ],
        ])
        ->assertSessionHasErrors('cantidad');

    expect($stock->fresh()->stock_actual)->toBe('1.000');
    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
    $this->assertDatabaseCount('inventory_movements', 0);
});

test('sale with boleta to client with RUC is allowed', function () {
    Queue::fake();

    $user = saleUser();
    $client = Client::factory()->create(['tipo_documento' => 'ruc']);
    $catalogItem = CatalogItem::factory()->create(['precio' => 100.00, 'controla_stock' => false]);

    $this->actingAs($user)
        ->post(route('sales.store'), [
            'client_id' => $client->id,
            'tipo_comprobante' => 'boleta',
            'fecha' => '2026-09-18',
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'catalog_item_id' => $catalogItem->id,
                    'cantidad' => 1,
                    'precio_unitario' => 100.00,
                    'descuento' => 0,
                ],
            ],
            'payments' => [
                [
                    'forma_pago' => 'efectivo',
                    'monto' => 118.00,
                    'referencia' => null,
                ],
            ],
        ])
        ->assertRedirect();

    $sale = Sale::where('client_id', $client->id)->firstOrFail();
    $document = ElectronicDocument::where('sale_id', $sale->id)->firstOrFail();

    expect($document->tipo)->toBe('boleta');
});

test('sale with factura to client without RUC fails validation', function () {
    $user = saleUser();
    $client = Client::factory()->create(['tipo_documento' => 'dni']);
    $catalogItem = CatalogItem::factory()->create(['precio' => 100.00, 'controla_stock' => false]);

    $this->actingAs($user)
        ->post(route('sales.store'), [
            'client_id' => $client->id,
            'tipo_comprobante' => 'factura',
            'fecha' => '2026-09-18',
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'catalog_item_id' => $catalogItem->id,
                    'cantidad' => 1,
                    'precio_unitario' => 100.00,
                    'descuento' => 0,
                ],
            ],
            'payments' => [
                [
                    'forma_pago' => 'efectivo',
                    'monto' => 118.00,
                    'referencia' => null,
                ],
            ],
        ])
        ->assertSessionHasErrors(['tipo_comprobante']);

    $this->assertDatabaseCount('sales', 0);
});
