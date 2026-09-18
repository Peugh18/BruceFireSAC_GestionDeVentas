<?php

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\ClientSite;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
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
    $catalogItem = CatalogItem::factory()->create(['precio' => 200.00, 'control_serializado' => true]);

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
        ->post(route('quotes.convert', $quote->id))
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
