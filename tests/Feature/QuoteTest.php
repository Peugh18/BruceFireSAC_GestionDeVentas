use App\Jobs\SendElectronicDocumentJob;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\ClientSite;
use App\Models\Equipment;
use App\Models\InventoryStock;
use App\Models\InventoryUnit;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function quoteUser(array $permissions = ['quotes.view', 'quotes.create', 'quotes.update', 'quotes.convert']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('quote routes redirect guests to login', function (string $method, string $path) {
    $this->{$method}($path)->assertRedirect(route('login'));
})->with([
    ['get', '/quotes'],
    ['get', '/quotes/create'],
    ['post', '/quotes'],
    ['get', '/quotes/1'],
    ['get', '/quotes/1/edit'],
    ['put', '/quotes/1'],
    ['post', '/quotes/1/duplicate'],
    ['patch', '/quotes/1/status'],
    ['post', '/quotes/1/convert'],
]);

test('quote routes forbid users without permissions', function () {
    $user = User::factory()->create();
    $quote = Quote::factory()->create();

    $this->actingAs($user)->get('/quotes')->assertForbidden();
    $this->actingAs($user)->get('/quotes/create')->assertForbidden();
    $this->actingAs($user)->get("/quotes/{$quote->id}")->assertForbidden();
    $this->actingAs($user)->get("/quotes/{$quote->id}/edit")->assertForbidden();
});

test('user with permission can view quotes list', function () {
    $user = quoteUser();
    $quote = Quote::factory()->create();

    $this->actingAs($user)
        ->get(route('quotes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('quotes/index')
            ->has('quotes.data', 1)
            ->where('quotes.data.0.id', $quote->id)
        );
});

test('user can create a quote with items', function () {
    $user = quoteUser();
    $client = Client::factory()->create();
    $site = ClientSite::factory()->create(['client_id' => $client->id]);
    $vehicle = Vehicle::factory()->create(['client_id' => $client->id]);
    $catalogItem = CatalogItem::factory()->create(['precio' => 100.00]);

    $payload = [
        'client_id' => $client->id,
        'client_site_id' => $site->id,
        'vehicle_id' => $vehicle->id,
        'fecha' => now()->toDateString(),
        'vigencia' => now()->addDays(15)->toDateString(),
        'condicion_propuesta' => 'contado',
        'observaciones' => 'Cotización de prueba',
        'items' => [
            [
                'catalog_item_id' => $catalogItem->id,
                'cantidad' => 2,
                'precio_unitario' => 100.00,
                'descuento' => 10.00,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post(route('quotes.store'), $payload);

    $this->assertDatabaseHas('quotes', [
        'client_id' => $client->id,
        'client_site_id' => $site->id,
        'vehicle_id' => $vehicle->id,
        'vendedor_user_id' => $user->id,
        'subtotal' => 190.00,
        'igv' => 34.20,
        'total' => 224.20,
        'estado' => 'borrador',
    ]);

    $quote = Quote::where('client_id', $client->id)->first();
    $response->assertRedirect(route('quotes.show', $quote));

    $this->assertDatabaseHas('quote_items', [
        'quote_id' => $quote->id,
        'catalog_item_id' => $catalogItem->id,
        'cantidad' => 2,
        'precio_unitario' => 100.00,
        'descuento' => 10.00,
        'subtotal' => 190.00,
    ]);
});

test('user can edit draft quote', function () {
    $user = quoteUser();
    $quote = Quote::factory()->create(['estado' => 'borrador']);
    QuoteItem::factory()->create(['quote_id' => $quote->id]);
    $newCatalogItem = CatalogItem::factory()->create(['precio' => 150.00]);

    $payload = [
        'client_id' => $quote->client_id,
        'client_site_id' => $quote->client_site_id,
        'vehicle_id' => $quote->vehicle_id,
        'fecha' => $quote->fecha->toDateString(),
        'vigencia' => $quote->vigencia->toDateString(),
        'condicion_propuesta' => 'credito',
        'observaciones' => 'Modificado',
        'items' => [
            [
                'catalog_item_id' => $newCatalogItem->id,
                'cantidad' => 1,
                'precio_unitario' => 150.00,
                'descuento' => 0,
            ],
        ],
    ];

    $this->actingAs($user)
        ->put(route('quotes.update', $quote->id), $payload)
        ->assertRedirect(route('quotes.show', $quote));

    $this->assertDatabaseHas('quotes', [
        'id' => $quote->id,
        'condicion_propuesta' => 'credito',
        'observaciones' => 'Modificado',
        'subtotal' => 150.00,
        'igv' => 27.00,
        'total' => 177.00,
    ]);
});

test('user can duplicate a quote', function () {
    $user = quoteUser();
    $quote = Quote::factory()->create(['estado' => 'emitida']);
    $item = QuoteItem::factory()->create(['quote_id' => $quote->id]);

    $this->actingAs($user)
        ->post(route('quotes.duplicate', $quote->id))
        ->assertRedirect();

    $this->assertDatabaseCount('quotes', 2);
    $duplicated = Quote::where('id', '!=', $quote->id)->first();

    expect($duplicated->client_id)->toBe($quote->client_id);
    expect($duplicated->estado)->toBe('borrador');
    expect($duplicated->items)->toHaveCount(1);
    expect($duplicated->items->first()->catalog_item_id)->toBe($item->catalog_item_id);
});

test('user can change quote status', function () {
    $user = quoteUser();
    $quote = Quote::factory()->create(['estado' => 'borrador']);

    $this->actingAs($user)
        ->patch(route('quotes.status', $quote->id), ['estado' => 'enviada'])
        ->assertRedirect(route('quotes.show', $quote));

    expect($quote->fresh()->estado)->toBe('enviada');
});

test('user can convert an accepted quote to sale without re-typing', function () {
    $user = quoteUser(['quotes.view', 'quotes.create', 'quotes.update', 'quotes.convert', 'sales.view']);
    $client = Client::factory()->create();
    $site = ClientSite::factory()->create(['client_id' => $client->id]);
    $vehicle = Vehicle::factory()->create(['client_id' => $client->id]);
    $catalogItem = CatalogItem::factory()->create(['precio' => 200.00, 'control_serializado' => false]);

    $quote = Quote::factory()->create([
        'client_id' => $client->id,
        'client_site_id' => $site->id,
        'vehicle_id' => $vehicle->id,
        'vendedor_user_id' => $user->id,
        'condicion_propuesta' => 'credito',
        'subtotal' => 200.00,
        'igv' => 36.00,
        'total' => 236.00,
        'observaciones' => 'Conversión especial',
        'estado' => 'aceptada',
    ]);

    QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'catalog_item_id' => $catalogItem->id,
        'cantidad' => 1,
        'precio_unitario' => 200.00,
        'descuento' => 0,
        'subtotal' => 200.00,
    ]);

    $this->actingAs($user)
        ->post(route('quotes.convert', $quote->id), ['tipo_comprobante' => 'boleta'])
        ->assertRedirect();

    // Verify quote updated to convertida
    expect($quote->fresh()->estado)->toBe('convertida');

    // Verify sale created with accurate fields
    $this->assertDatabaseHas('sales', [
        'quote_id' => $quote->id,
        'client_id' => $client->id,
        'client_site_id' => $site->id,
        'vehicle_id' => $vehicle->id,
        'vendedor_user_id' => $user->id,
        'condicion_pago' => 'credito',
        'subtotal' => 200.00,
        'igv' => 36.00,
        'total' => 236.00,
        'observaciones' => 'Conversión especial',
        'estado' => 'completada',
    ]);

    $sale = Sale::where('quote_id', $quote->id)->first();
    expect($sale)->not->toBeNull();

    // Verify sale item copied
    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'catalog_item_id' => $catalogItem->id,
        'cantidad' => 1,
        'precio_unitario' => 200.00,
        'subtotal' => 200.00,
    ]);
});

test('quote cannot be converted to sale unless it was accepted', function (string $estado) {
    $user = quoteUser(['quotes.view', 'quotes.create', 'quotes.update', 'quotes.convert', 'sales.view']);
    $quote = Quote::factory()->create(['estado' => $estado]);

    $this->actingAs($user)
        ->post(route('quotes.convert', $quote->id))
        ->assertForbidden();

    expect($quote->fresh()->estado)->toBe($estado);
    $this->assertDatabaseCount('sales', 0);
})->with(['borrador', 'emitida', 'enviada', 'rechazada', 'vencida']);

test('converting a quote with serialized item fails when unit is missing', function () {
    $user = quoteUser(['quotes.view', 'quotes.create', 'quotes.update', 'quotes.convert', 'sales.view']);
    $client = Client::factory()->create();
    $catalogItem = CatalogItem::factory()->create([
        'precio' => 250.00,
        'controla_stock' => true,
        'control_serializado' => true,
    ]);

    $quote = Quote::factory()->create([
        'client_id' => $client->id,
        'vendedor_user_id' => $user->id,
        'estado' => 'aceptada',
        'subtotal' => 250.00,
        'igv' => 45.00,
        'total' => 295.00,
    ]);

    $item = QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'catalog_item_id' => $catalogItem->id,
        'cantidad' => 1,
        'precio_unitario' => 250.00,
        'subtotal' => 250.00,
    ]);

    $this->actingAs($user)
        ->post(route('quotes.convert', $quote->id), [
            'tipo_comprobante' => 'boleta',
            'items' => [],
        ])
        ->assertSessionHasErrors(["items.{$item->id}.inventory_unit_id"]);

    expect($quote->fresh()->estado)->toBe('aceptada');
    $this->assertDatabaseCount('sales', 0);
});

test('converting a quote with serialized item requires unit, discounts stock and creates equipment', function () {
    Queue::fake();

    $user = quoteUser(['quotes.view', 'quotes.create', 'quotes.update', 'quotes.convert', 'sales.view']);
    $client = Client::factory()->create(['tipo_documento' => 'ruc']);
    $site = ClientSite::factory()->create(['client_id' => $client->id]);
    $vehicle = Vehicle::factory()->create(['client_id' => $client->id]);

    $catalogItem = CatalogItem::factory()->create([
        'nombre' => 'Extintor Acetato 6L',
        'precio' => 300.00,
        'controla_stock' => true,
        'control_serializado' => true,
        'genera_barcode' => true,
    ]);

    $stock = InventoryStock::where('catalog_item_id', $catalogItem->id)->firstOrFail();
    $stock->update(['stock_actual' => 3]);

    $unit = InventoryUnit::factory()->create([
        'catalog_item_id' => $catalogItem->id,
        'serie' => 'SER-QUOTE-001',
        'marca' => 'Buckeye',
        'capacidad' => '6 L',
        'anio' => 2026,
        'barcode' => 'EXT-ACET-001',
        'en_stock' => true,
        'conforme' => true,
        'estado' => 'disponible',
    ]);

    $quote = Quote::factory()->create([
        'client_id' => $client->id,
        'client_site_id' => $site->id,
        'vehicle_id' => $vehicle->id,
        'vendedor_user_id' => $user->id,
        'condicion_propuesta' => 'contado',
        'subtotal' => 300.00,
        'igv' => 54.00,
        'total' => 354.00,
        'estado' => 'aceptada',
    ]);

    $quoteItem = QuoteItem::factory()->create([
        'quote_id' => $quote->id,
        'catalog_item_id' => $catalogItem->id,
        'cantidad' => 1,
        'precio_unitario' => 300.00,
        'descuento' => 0,
        'subtotal' => 300.00,
    ]);

    $this->actingAs($user)
        ->post(route('quotes.convert', $quote->id), [
            'tipo_comprobante' => 'factura',
            'items' => [
                [
                    'quote_item_id' => $quoteItem->id,
                    'inventory_unit_id' => $unit->id,
                ],
            ],
        ])
        ->assertRedirect();

    expect($quote->fresh()->estado)->toBe('convertida');

    $sale = Sale::where('quote_id', $quote->id)->firstOrFail();

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'catalog_item_id' => $catalogItem->id,
        'inventory_unit_id' => $unit->id,
        'cantidad' => 1,
    ]);

    expect($stock->fresh()->stock_actual)->toBe('2.000');

    $unit->refresh();
    expect($unit->en_stock)->toBeFalse();
    expect($unit->estado)->toBe('vendido');

    $this->assertDatabaseHas('equipment', [
        'client_id' => $client->id,
        'client_site_id' => $site->id,
        'vehicle_id' => $vehicle->id,
        'origen' => 'vendido_bruce_fire',
        'tipo_equipo' => 'Extintor Acetato 6L',
        'marca' => 'Buckeye',
        'capacidad' => '6 L',
        'serie_fabricante' => 'SER-QUOTE-001',
        'anio_fabricacion' => '2026',
        'barcode' => 'EXT-ACET-001',
        'estado' => 'activo',
    ]);

    Queue::assertPushed(SendElectronicDocumentJob::class);
});
