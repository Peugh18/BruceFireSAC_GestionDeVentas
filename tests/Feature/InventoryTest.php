<?php

use App\Models\CatalogItem;
use App\Models\InventoryMovement;
use App\Models\InventoryReception;
use App\Models\InventoryStock;
use App\Models\InventoryUnit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function inventoryUser(array $permissions = ['inventory.view', 'inventory.receive', 'inventory.adjust']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

function inventoryReceipt(CatalogItem $item, array $overrides = []): array
{
    return array_replace([
        'catalog_item_id' => $item->id,
        'proveedor' => 'Proveedor de seguridad SAC',
        'documento_referencia' => 'GR-001-123',
        'fecha' => '2026-01-15',
        'cantidad' => '5.000',
        'cantidad_conforme' => '4.000',
        'cantidad_observada' => '1.000',
        'observacion' => 'Una unidad con daño de transporte.',
        'units' => [],
    ], $overrides);
}

function inventoryMove(array $overrides = []): array
{
    return array_replace([
        'tipo' => 'salida', 'cantidad' => '2', 'motivo' => 'Consumo interno',
        'fecha' => '2026-01-15', 'referencia' => 'REQ-001', 'unit_ids' => [],
    ], $overrides);
}

test('inventory routes require authentication', function (string $method, string $url) {
    $this->{$method}($url)->assertRedirect(route('login'));
})->with([
    ['get', '/inventory'], ['get', '/inventory/receive'], ['post', '/inventory/receive'],
    ['get', '/inventory/movements'], ['get', '/inventory/1'], ['patch', '/inventory/1'], ['post', '/inventory/1/movements'],
]);

test('inventory policy respects the existing role matrix', function (string $role, bool $view, bool $write) {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create()->assignRole($role);

    expect($user->can('viewAny', InventoryStock::class))->toBe($view);
    expect($user->can('receive', InventoryStock::class))->toBe($write);
    expect($user->can('adjust', InventoryStock::class))->toBe($write);
})->with([
    ['Almacén', true, true], ['Gerente', true, true],
    ['Vendedor', false, false], ['Técnico de Planta', false, false], ['Técnico de Campo', false, false],
]);

test('inventory routes reject users without permissions without changing stock', function (string $method, string $url) {
    $stock = InventoryStock::factory()->create(['stock_actual' => 8]);
    $this->actingAs(User::factory()->create())->{$method}(str_replace('{id}', (string) $stock->id, $url))->assertForbidden();

    expect($stock->fresh()->stock_actual)->toBe('8.000');
    $this->assertDatabaseCount('inventory_receptions', 0);
    $this->assertDatabaseCount('inventory_movements', 0);
})->with([
    ['get', '/inventory'], ['get', '/inventory/receive'], ['post', '/inventory/receive'],
    ['get', '/inventory/movements'], ['get', '/inventory/{id}'], ['patch', '/inventory/{id}'], ['post', '/inventory/{id}/movements'],
]);

test('read only users cannot receive adjust or change minimums', function () {
    $stock = InventoryStock::factory()->create();
    $this->actingAs(inventoryUser(['inventory.view']));

    $this->post(route('inventory.receive.store'), inventoryReceipt($stock->catalogItem))->assertForbidden();
    $this->get(route('inventory.receive'))->assertForbidden();
    $this->post(route('inventory.movements.store', $stock), inventoryMove())->assertForbidden();
    $this->patch(route('inventory.update', $stock), ['stock_minimo' => 5])->assertForbidden();
    $this->get(route('inventory.show', $stock))->assertInertia(fn (Assert $page) => $page->where('canAdjust', false));
    $this->assertDatabaseCount('inventory_movements', 0);
});

test('write permissions alone do not grant inventory access', function (string $permission, string $ability) {
    $user = inventoryUser([$permission]);

    expect($user->can($ability, InventoryStock::class))->toBeFalse();
    $this->actingAs($user)->get(route('inventory.index'))->assertForbidden();
})->with([['inventory.receive', 'receive'], ['inventory.adjust', 'adjust']]);

test('stock rows are created once when catalog stock control is enabled', function () {
    $item = CatalogItem::factory()->create(['controla_stock' => false]);
    CatalogItem::factory()->service()->create();
    $this->assertDatabaseCount('inventory_stocks', 0);

    $item->update(['controla_stock' => true]);
    $item->update(['nombre' => 'Nombre actualizado']);

    $this->assertDatabaseCount('inventory_stocks', 1);
    $this->assertDatabaseHas('inventory_stocks', ['catalog_item_id' => $item->id, 'stock_actual' => 0, 'stock_minimo' => 0]);
});

test('receiving stock stores supplier inspection and trusted actor and increments only conforming quantity', function () {
    $stock = InventoryStock::factory()->create(['stock_actual' => '2.125']);
    $user = inventoryUser();
    $payload = inventoryReceipt($stock->catalogItem, ['usuario_id' => 99999, 'stock_actual' => 1000]);

    $this->actingAs($user)->post(route('inventory.receive.store'), $payload)
        ->assertSessionHasNoErrors()->assertRedirect(route('inventory.index'));

    expect($stock->fresh()->stock_actual)->toBe('6.125');
    $this->assertDatabaseHas('inventory_receptions', [
        'catalog_item_id' => $stock->catalog_item_id, 'proveedor' => 'Proveedor de seguridad SAC',
        'documento_referencia' => 'GR-001-123', 'cantidad' => 5, 'cantidad_conforme' => 4,
        'cantidad_observada' => 1, 'usuario_id' => $user->id,
    ]);
    $this->assertDatabaseHas('inventory_movements', [
        'tipo' => 'entrada', 'cantidad' => 4, 'stock_antes' => 2.125, 'stock_despues' => 6.125,
        'inventory_reception_id' => InventoryReception::sole()->id, 'usuario_id' => $user->id,
    ]);
});

test('serialized reception captures every series and retains observed units outside conforming stock', function () {
    $item = CatalogItem::factory()->create(['control_serializado' => true, 'genera_barcode' => true]);
    $units = [
        ['serie' => 'SER-01', 'marca' => 'Marca A', 'capacidad' => '6 kg', 'anio' => 2025, 'conforme' => true],
        ['serie' => 'SER-02', 'marca' => 'Marca A', 'capacidad' => '6 kg', 'anio' => 2025, 'conforme' => false],
    ];

    $this->actingAs(inventoryUser())->post(route('inventory.receive.store'), inventoryReceipt($item, [
        'cantidad' => 2, 'cantidad_conforme' => 1, 'cantidad_observada' => 1, 'units' => $units,
    ]))->assertSessionHasNoErrors()->assertRedirect(route('inventory.index'));

    $this->assertDatabaseHas('inventory_stocks', ['catalog_item_id' => $item->id, 'stock_actual' => 1]);
    $this->assertDatabaseHas('inventory_units', ['serie' => 'SER-01', 'estado' => 'disponible', 'en_stock' => true, 'conforme' => true]);
    $this->assertDatabaseHas('inventory_units', ['serie' => 'SER-02', 'estado' => 'reservado', 'en_stock' => false, 'conforme' => false]);
    expect(InventoryUnit::where('serie', 'SER-01')->first()->barcode)->toStartWith('INV-');
    expect(InventoryReception::sole()->units)->toHaveCount(2);
});

test('fully observed reception leaves stock unchanged and remains in history', function () {
    $stock = InventoryStock::factory()->create(['stock_actual' => 3]);

    $this->actingAs(inventoryUser())->post(route('inventory.receive.store'), inventoryReceipt($stock->catalogItem, [
        'cantidad_conforme' => 0, 'cantidad_observada' => 5,
    ]))->assertSessionHasNoErrors();

    expect($stock->fresh()->stock_actual)->toBe('3.000');
    $this->assertDatabaseHas('inventory_movements', ['tipo' => 'entrada', 'cantidad' => 0, 'stock_antes' => 3, 'stock_despues' => 3]);
});

test('invalid reception quantities and dates do not create movements', function (array $changes, string $field) {
    $item = CatalogItem::factory()->create();

    $this->actingAs(inventoryUser())->post(route('inventory.receive.store'), inventoryReceipt($item, $changes))->assertSessionHasErrors($field);

    $this->assertDatabaseCount('inventory_receptions', 0);
    $this->assertDatabaseCount('inventory_movements', 0);
    $this->assertDatabaseHas('inventory_stocks', ['catalog_item_id' => $item->id, 'stock_actual' => 0]);
})->with([
    'unbalanced quantities' => [['cantidad' => 6], 'cantidad'],
    'negative' => [['cantidad_conforme' => -1], 'cantidad_conforme'],
    'precision' => [['cantidad' => '5.0001'], 'cantidad'],
    'overflow' => [['cantidad' => '1000000'], 'cantidad'],
    'zero' => [['cantidad' => 0], 'cantidad'],
    'future date' => [['fecha' => '2999-01-01'], 'fecha'],
    'invalid date' => [['fecha' => '2026-02-30'], 'fecha'],
    'missing observation' => [['observacion' => ''], 'observacion'],
    'array quantity' => [['cantidad' => []], 'cantidad'],
    'unknown item' => [['catalog_item_id' => 99999], 'catalog_item_id'],
]);

test('receiving requires the supplier reference date and quantities', function () {
    $this->actingAs(inventoryUser())->post(route('inventory.receive.store'), [])
        ->assertSessionHasErrors(['proveedor', 'documento_referencia', 'fecha', 'catalog_item_id', 'cantidad', 'cantidad_conforme', 'cantidad_observada']);
    $this->assertDatabaseCount('inventory_receptions', 0);
});

test('receiving rejects services non stock items and inactive items', function (string $state, array $attributes) {
    $item = CatalogItem::factory()->{$state}()->create($attributes);

    $this->actingAs(inventoryUser())->post(route('inventory.receive.store'), inventoryReceipt($item))
        ->assertSessionHasErrors(['catalog_item_id' => 'Selecciona un artículo activo que controle stock.']);

    $this->assertDatabaseCount('inventory_movements', 0);
})->with([
    ['service', []], ['product', ['controla_stock' => false]], ['inactive', []],
]);

test('serialized reception rejects incomplete duplicate and inconsistent series without partial writes', function (string $case) {
    $item = CatalogItem::factory()->create(['control_serializado' => true]);
    $unit = ['serie' => 'SER-01', 'marca' => 'Marca', 'capacidad' => '6 kg', 'anio' => 2025, 'conforme' => true];
    $units = [$unit, [...$unit, 'serie' => 'SER-02']];
    if ($case === 'duplicate') {
        $units[1]['serie'] = 'SER-01';
    }
    if ($case === 'missing') {
        array_pop($units);
    }
    if ($case === 'condition') {
        $units[1]['conforme'] = false;
    }
    if ($case === 'existing') {
        InventoryUnit::factory()->create(['serie' => 'SER-01']);
    }

    $this->actingAs(inventoryUser())->post(route('inventory.receive.store'), inventoryReceipt($item, [
        'cantidad' => 2, 'cantidad_conforme' => 2, 'cantidad_observada' => 0, 'units' => $units,
    ]))->assertSessionHasErrors();

    $this->assertDatabaseCount('inventory_receptions', 0);
    $this->assertDatabaseCount('inventory_movements', 0);
    $this->assertDatabaseHas('inventory_stocks', ['catalog_item_id' => $item->id, 'stock_actual' => 0]);
})->with(['duplicate', 'missing', 'condition', 'existing']);

test('a database failure after writing a reception rolls back receipt units and stock', function () {
    $item = CatalogItem::factory()->create(['control_serializado' => true]);
    InventoryUnit::factory()->create(['barcode' => 'DUPLICATE']);
    $user = inventoryUser();
    $payload = inventoryReceipt($item, [
        'cantidad' => 2, 'cantidad_conforme' => 2, 'cantidad_observada' => 0,
        'units' => [
            ['serie' => 'NEW-01', 'marca' => 'Marca', 'capacidad' => '6 kg', 'anio' => 2025, 'conforme' => true],
            ['serie' => 'NEW-02', 'marca' => 'Marca', 'capacidad' => '6 kg', 'anio' => 2025, 'conforme' => true, 'barcode' => 'DUPLICATE'],
        ],
    ]);

    expect(fn () => InventoryReception::receive($payload, $user))->toThrow(UniqueConstraintViolationException::class);

    $this->assertDatabaseCount('inventory_receptions', 0);
    $this->assertDatabaseCount('inventory_movements', 0);
    $this->assertDatabaseMissing('inventory_units', ['serie' => 'NEW-01']);
    $this->assertDatabaseHas('inventory_stocks', ['catalog_item_id' => $item->id, 'stock_actual' => 0]);
});

test('authorized adjustments and withdrawals update stock and record before and after', function (string $type, string $amount, string $expected) {
    $stock = InventoryStock::factory()->create(['stock_actual' => '5.125']);
    $user = inventoryUser();

    $this->actingAs($user)->post(route('inventory.movements.store', $stock), inventoryMove(['tipo' => $type, 'cantidad' => $amount, 'usuario_id' => 9999]))
        ->assertSessionHasNoErrors()->assertRedirect(route('inventory.show', $stock));

    expect($stock->fresh()->stock_actual)->toBe($expected);
    $this->assertDatabaseHas('inventory_movements', [
        'catalog_item_id' => $stock->catalog_item_id, 'tipo' => $type, 'cantidad' => $amount,
        'stock_antes' => '5.125', 'stock_despues' => $expected, 'usuario_id' => $user->id,
        'motivo' => 'Consumo interno', 'referencia' => 'REQ-001',
    ]);
})->with([
    ['salida', '2.125', '3.000'], ['ajuste', '-0.125', '5.000'], ['ajuste', '0.250', '5.375'],
]);

test('invalid movements do not change stock or create history', function (array $changes, string $field) {
    $stock = InventoryStock::factory()->create(['stock_actual' => 5]);

    $this->actingAs(inventoryUser())->post(route('inventory.movements.store', $stock), inventoryMove($changes))->assertSessionHasErrors($field);

    expect($stock->fresh()->stock_actual)->toBe('5.000');
    $this->assertDatabaseCount('inventory_movements', 0);
})->with([
    [['cantidad' => 6], 'cantidad'], [['tipo' => 'ajuste', 'cantidad' => -6], 'cantidad'],
    [['cantidad' => 0], 'cantidad'], [['cantidad' => -1], 'cantidad'],
    [['tipo' => 'venta'], 'tipo'], [['tipo' => 'entrada'], 'tipo'],
    [['motivo' => ''], 'motivo'], [['cantidad' => '1.1234'], 'cantidad'],
    [['unit_ids' => [1]], 'unit_ids'],
]);

test('serialized withdrawals preserve series history and never mark units as sold', function () {
    $item = CatalogItem::factory()->create(['control_serializado' => true]);
    $stock = InventoryStock::where('catalog_item_id', $item->id)->first();
    $stock->update(['stock_actual' => 2]);
    $unit = InventoryUnit::factory()->for($item)->create();
    InventoryUnit::factory()->for($item)->create();

    $this->actingAs(inventoryUser())->post(route('inventory.movements.store', $stock), inventoryMove(['cantidad' => 1, 'unit_ids' => [$unit->id]]))
        ->assertSessionHasNoErrors();

    expect($stock->fresh()->stock_actual)->toBe('1.000');
    expect($unit->fresh()->en_stock)->toBeFalse();
    expect($unit->fresh()->estado)->toBe('disponible');
    expect($unit->fresh()->salida_movement_id)->toBe(InventoryMovement::sole()->id);
});

test('serialized withdrawals reject unavailable or unrelated units', function (string $state) {
    $item = CatalogItem::factory()->create(['control_serializado' => true]);
    $stock = InventoryStock::where('catalog_item_id', $item->id)->first();
    $stock->update(['stock_actual' => 2]);
    $unit = InventoryUnit::factory()->for($item)->create();
    if ($state === 'other item') {
        $unit->update(['catalog_item_id' => CatalogItem::factory()->create()->id]);
    } elseif ($state === 'withdrawn') {
        $unit->update(['en_stock' => false]);
    } elseif ($state === 'observed') {
        $unit->update(['conforme' => false, 'en_stock' => false, 'estado' => 'reservado']);
    } else {
        $unit->update(['estado' => $state]);
    }

    $this->actingAs(inventoryUser())->post(route('inventory.movements.store', $stock), inventoryMove(['cantidad' => 1, 'unit_ids' => [$unit->id]]))
        ->assertSessionHasErrors('unit_ids');

    expect($stock->fresh()->stock_actual)->toBe('2.000');
    $this->assertDatabaseCount('inventory_movements', 0);
})->with(['other item', 'withdrawn', 'observed', 'reservado', 'vendido']);

test('serialized stock cannot increase without new series or decrease by fractions', function (array $changes) {
    $item = CatalogItem::factory()->create(['control_serializado' => true]);
    $stock = InventoryStock::where('catalog_item_id', $item->id)->first();
    $stock->update(['stock_actual' => 2]);

    $this->actingAs(inventoryUser())->post(route('inventory.movements.store', $stock), inventoryMove($changes))->assertSessionHasErrors('cantidad');

    $this->assertDatabaseCount('inventory_movements', 0);
})->with([[['tipo' => 'ajuste', 'cantidad' => 1]], [['cantidad' => 0.5]]]);

test('minimum stock alert is strict and updates without changing physical stock', function () {
    $stock = InventoryStock::factory()->create(['stock_actual' => 3, 'stock_minimo' => 3]);
    $this->actingAs(inventoryUser());
    $this->get(route('inventory.index'))->assertInertia(fn (Assert $page) => $page->where('lowStockCount', 0));

    $this->patch(route('inventory.update', $stock), ['stock_minimo' => 4, 'stock_actual' => 999])->assertSessionHasNoErrors();
    $this->get(route('inventory.index', ['low_stock' => 1]))->assertInertia(fn (Assert $page) => $page
        ->where('lowStockCount', 1)->where('stocks.total', 1)->where('stocks.data.0.stock_actual', '3.000')->where('stocks.data.0.stock_minimo', '4.000'));
    $this->assertDatabaseCount('inventory_movements', 0);
});

test('invalid stock minimum is rejected', function () {
    $stock = InventoryStock::factory()->create();

    $this->actingAs(inventoryUser())->patch(route('inventory.update', $stock), ['stock_minimo' => -1])
        ->assertSessionHasErrors(['stock_minimo' => 'El stock mínimo no puede ser negativo.']);

    expect($stock->fresh()->stock_minimo)->toBe('0.000');
});

test('inventory search paginates and excludes catalog items without stock control', function () {
    CatalogItem::factory()->count(16)->create(['nombre' => 'Artículo buscado']);
    CatalogItem::factory()->create(['nombre' => 'Otro']);
    CatalogItem::factory()->create(['nombre' => 'Artículo buscado', 'controla_stock' => false]);

    $this->actingAs(inventoryUser())->get(route('inventory.index', ['search' => 'buscado', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->component('inventory/index')->where('stocks.total', 16)
            ->has('stocks.data', 1)->where('stocks.current_page', 2)->where('filters.search', 'buscado'));
});

test('movement history combines item type date and search filters and shows supplier details', function () {
    $item = CatalogItem::factory()->create();
    $reception = InventoryReception::factory()->for($item)->create(['proveedor' => 'Proveedor buscado']);
    InventoryMovement::factory()->count(16)->for($item)->create(['inventory_reception_id' => null, 'tipo' => 'ajuste', 'fecha' => '2026-01-10', 'motivo' => 'Conteo buscado']);
    InventoryMovement::factory()->for($item)->create(['fecha' => '2026-01-10', 'inventory_reception_id' => $reception->id]);
    $this->actingAs(inventoryUser(['inventory.view']));

    $this->get(route('inventory.movements', ['tipo' => 'ajuste', 'search' => 'buscado', 'catalog_item_id' => $item->id, 'desde' => '2026-01-01', 'hasta' => '2026-01-31', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->component('inventory/movements')->where('movements.total', 16)->has('movements.data', 1)
            ->where('movements.data.0.tipo', 'ajuste')->where('filters.catalog_item_id', (string) $item->id));
    $this->get(route('inventory.movements', ['search' => 'Proveedor buscado']))
        ->assertInertia(fn (Assert $page) => $page->where('movements.total', 1)->where('movements.data.0.reception.proveedor', 'Proveedor buscado'));
});

test('receive page lists only eligible catalog items', function () {
    $item = CatalogItem::factory()->create();
    CatalogItem::factory()->inactive()->create();
    CatalogItem::factory()->service()->create();

    $this->actingAs(inventoryUser())->get(route('inventory.receive'))->assertInertia(fn (Assert $page) => $page
        ->component('inventory/receive')->has('items', 1)->where('items.0.id', $item->id));
});

test('inventory detail paginates units and missing stock returns not found', function () {
    $item = CatalogItem::factory()->create(['control_serializado' => true]);
    $stock = InventoryStock::where('catalog_item_id', $item->id)->first();
    InventoryUnit::factory()->count(26)->for($item)->create();

    $this->actingAs(inventoryUser())->get(route('inventory.show', ['stock' => $stock, 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->component('inventory/show')->where('units.total', 26)->has('units.data', 1));
    $this->get(route('inventory.show', 9999))->assertNotFound();
});

test('warehouse role receives decimal quantities without serial data', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create()->assignRole('Almacén');
    $item = CatalogItem::factory()->create();
    $payload = inventoryReceipt($item, ['cantidad' => '0.3', 'cantidad_conforme' => '0.2', 'cantidad_observada' => '0.1']);
    unset($payload['units']);

    $this->actingAs($user)->post(route('inventory.receive.store'), $payload)->assertSessionHasNoErrors();

    $this->assertDatabaseHas('inventory_stocks', ['catalog_item_id' => $item->id, 'stock_actual' => 0.2]);
    $this->assertDatabaseHas('inventory_movements', ['usuario_id' => $user->id, 'cantidad' => 0.2]);
});

test('serialized reception accepts blank optional barcodes from the form', function () {
    $item = CatalogItem::factory()->create(['control_serializado' => true]);
    $unit = ['serie' => 'BLANK-01', 'marca' => 'Marca', 'capacidad' => '6 kg', 'anio' => '2025', 'conforme' => true, 'barcode' => ''];

    $this->actingAs(inventoryUser())->post(route('inventory.receive.store'), inventoryReceipt($item, [
        'cantidad' => '2', 'cantidad_conforme' => '2', 'cantidad_observada' => '0',
        'units' => [$unit, [...$unit, 'serie' => 'BLANK-02']],
    ]))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('inventory_units', 2);
    $this->assertDatabaseHas('inventory_units', ['serie' => 'BLANK-01', 'barcode' => null]);
});

test('reception rejects existing barcodes and forged unit attributes', function (string $case) {
    $item = CatalogItem::factory()->create(['control_serializado' => true]);
    $unit = ['serie' => 'NEW-01', 'marca' => 'Marca', 'capacidad' => '6 kg', 'anio' => 2025, 'conforme' => true];
    if ($case === 'barcode') {
        InventoryUnit::factory()->create(['barcode' => 'TAKEN']);
        $unit['barcode'] = 'TAKEN';
    } else {
        $unit['estado'] = 'vendido';
    }

    $this->actingAs(inventoryUser())->post(route('inventory.receive.store'), inventoryReceipt($item, [
        'cantidad' => 1, 'cantidad_conforme' => 1, 'cantidad_observada' => 0, 'units' => [$unit],
    ]))->assertSessionHasErrors();

    $this->assertDatabaseCount('inventory_receptions', 0);
    $this->assertDatabaseMissing('inventory_units', ['serie' => 'NEW-01']);
})->with(['barcode', 'forged state']);

test('reception cannot overflow the stock balance', function () {
    $stock = InventoryStock::factory()->create(['stock_actual' => '999999999.999']);

    $this->actingAs(inventoryUser())->post(route('inventory.receive.store'), inventoryReceipt($stock->catalogItem))
        ->assertSessionHasErrors('cantidad_conforme');

    expect($stock->fresh()->stock_actual)->toBe('999999999.999');
    $this->assertDatabaseCount('inventory_receptions', 0);
    $this->assertDatabaseCount('inventory_movements', 0);
});

test('movements reject items whose stock control was disabled while preserving history', function () {
    $stock = InventoryStock::factory()->create(['stock_actual' => 5]);
    $stock->catalogItem->update(['controla_stock' => false]);

    $this->actingAs(inventoryUser())->post(route('inventory.movements.store', $stock), inventoryMove())->assertSessionHasErrors('cantidad');

    expect($stock->fresh()->stock_actual)->toBe('5.000');
    $this->assertDatabaseCount('inventory_movements', 0);
});

test('serialized withdrawals require matching distinct unit counts', function (string $case) {
    $item = CatalogItem::factory()->create(['control_serializado' => true]);
    $stock = InventoryStock::where('catalog_item_id', $item->id)->first();
    $stock->update(['stock_actual' => 2]);
    $unit = InventoryUnit::factory()->for($item)->create();
    $ids = match ($case) {
        'missing' => [],
        'duplicate' => [$unit->id, $unit->id],
        default => [$unit->id],
    };

    $this->actingAs(inventoryUser())->post(route('inventory.movements.store', $stock), inventoryMove(['cantidad' => 2, 'unit_ids' => $ids]))
        ->assertSessionHasErrors();

    expect($stock->fresh()->stock_actual)->toBe('2.000');
    expect($unit->fresh()->en_stock)->toBeTrue();
    $this->assertDatabaseCount('inventory_movements', 0);
})->with(['missing', 'duplicate', 'count mismatch']);

test('movement date filters allow an end date without a start date', function () {
    InventoryMovement::factory()->create(['fecha' => '2026-01-10']);
    InventoryMovement::factory()->create(['fecha' => '2026-02-10']);

    $this->actingAs(inventoryUser())->get(route('inventory.movements', ['hasta' => '2026-01-31']))
        ->assertSessionHasNoErrors()->assertInertia(fn (Assert $page) => $page->where('movements.total', 1)->where('movements.data.0.fecha', '2026-01-10'));
});

test('inventory rejects invalid list filters', function (string $routeName, array $filters, string $field) {
    $this->actingAs(inventoryUser())->get(route($routeName, $filters))->assertSessionHasErrors($field);
})->with([
    ['inventory.index', ['search' => ['invalid']], 'search'],
    ['inventory.index', ['page' => -1], 'page'],
    ['inventory.index', ['low_stock' => 'yes'], 'low_stock'],
    ['inventory.movements', ['tipo' => 'venta'], 'tipo'],
    ['inventory.movements', ['desde' => '2026-02-01', 'hasta' => '2026-01-01'], 'hasta'],
]);
