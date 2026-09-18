<?php

use App\Models\CatalogItem;
use App\Models\User;
use Database\Seeders\CatalogPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function catalogUser(array $permissions = ['catalog.view', 'catalog.create', 'catalog.update']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('catalog routes redirect guests to login', function (string $method, string $path) {
    $this->{$method}($path)->assertRedirect(route('login'));
})->with([
    ['get', '/catalog'], ['get', '/catalog/create'],
    ['post', '/catalog'], ['get', '/catalog/1/edit'], ['put', '/catalog/1'],
]);

test('catalog routes forbid users without permissions and do not write', function (string $method, string $path) {
    $item = CatalogItem::factory()->create(['nombre' => 'Original']);
    $user = User::factory()->create();

    $this->actingAs($user)->{$method}(str_replace('{id}', (string) $item->id, $path))
        ->assertForbidden();

    $this->assertDatabaseCount('catalog_items', 1);
    $this->assertDatabaseHas('catalog_items', ['id' => $item->id, 'nombre' => 'Original']);
})->with([
    ['get', '/catalog'], ['get', '/catalog/create'],
    ['post', '/catalog'], ['get', '/catalog/{id}/edit'], ['put', '/catalog/{id}'],
]);

test('catalog policy requires view plus the specific write permission', function (array $permissions, bool $view, bool $create, bool $update) {
    $user = catalogUser($permissions);
    $item = CatalogItem::factory()->make();

    expect($user->can('viewAny', CatalogItem::class))->toBe($view);
    expect($user->can('create', CatalogItem::class))->toBe($create);
    expect($user->can('update', $item))->toBe($update);
})->with([
    'none' => [[], false, false, false],
    'view only' => [['catalog.view'], true, false, false],
    'create without view' => [['catalog.create'], false, false, false],
    'update without view' => [['catalog.update'], false, false, false],
    'creator' => [['catalog.view', 'catalog.create'], true, true, false],
    'editor' => [['catalog.view', 'catalog.update'], true, false, true],
    'maintainer' => [['catalog.view', 'catalog.create', 'catalog.update'], true, true, true],
]);

test('read only users cannot access forms or write catalog records', function (string $method, string $path) {
    $item = CatalogItem::factory()->create(['nombre' => 'Original']);

    $this->actingAs(catalogUser(['catalog.view']))
        ->{$method}(str_replace('{id}', (string) $item->id, $path), ['nombre' => 'Changed'])
        ->assertForbidden();

    $this->assertDatabaseCount('catalog_items', 1);
    $this->assertDatabaseHas('catalog_items', ['id' => $item->id, 'nombre' => 'Original']);
})->with([
    ['get', '/catalog/create'], ['post', '/catalog'],
    ['get', '/catalog/{id}/edit'], ['put', '/catalog/{id}'],
]);

test('catalog listing combines type and search filters and retains pagination', function () {
    CatalogItem::factory()->service()->count(16)->create(['nombre' => 'Recarga PQS']);
    CatalogItem::factory()->product()->create(['nombre' => 'Recarga PQS']);
    CatalogItem::factory()->service()->create(['nombre' => 'Inspección']);

    $response = $this->actingAs(catalogUser(['catalog.view']))
        ->get(route('catalog.index', ['tipo' => 'servicio', 'search' => 'Recarga', 'page' => 2]));

    $response->assertInertia(fn (Assert $page) => $page->component('catalog/index')
        ->has('items.data', 1)
        ->where('items.total', 16)
        ->where('items.current_page', 2)
        ->where('items.data.0.tipo', 'servicio')
        ->where('filters.search', 'Recarga')
        ->where('can.create', false)
        ->where('can.update', false)
        ->where('auth.permissions', fn ($permissions): bool => collect($permissions)->contains('catalog.view'))
        ->where('items.prev_page_url', fn (string $url): bool => str_contains($url, 'tipo=servicio') && str_contains($url, 'search=Recarga')));
});

test('catalog can be searched by generated code or category', function (string $field) {
    $item = CatalogItem::factory()->create(['categoria' => 'Extintores especiales']);
    CatalogItem::factory()->create(['categoria' => 'Cámaras']);

    $this->actingAs(catalogUser(['catalog.view']))->get(route('catalog.index', ['search' => $item->{$field}]))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.id', $item->id));
})->with(['codigo', 'categoria']);

test('catalog rejects invalid filters', function () {
    $this->actingAs(catalogUser(['catalog.view']))
        ->get(route('catalog.index', ['tipo' => 'invalid', 'search' => ['bad'], 'page' => -1]))
        ->assertSessionHasErrors(['tipo', 'search', 'page']);
});

test('catalog treats SQL fragments as search text', function () {
    CatalogItem::factory()->create();

    $this->actingAs(catalogUser(['catalog.view']))
        ->get(route('catalog.index', ['search' => "' OR 1=1 --"]))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
});

test('catalog renders authorized create and edit pages', function () {
    $item = CatalogItem::factory()->service()->create();
    $this->actingAs(catalogUser());

    $this->get(route('catalog.create'))->assertInertia(fn (Assert $page) => $page->component('catalog/create'));
    $this->get(route('catalog.edit', $item))->assertInertia(fn (Assert $page) => $page->component('catalog/edit')
        ->where('item.id', $item->id)->where('item.tipo', 'servicio'));
});

test('catalog creates each type with an automatic code and exact price', function (string $state, string $tipo) {
    $payload = CatalogItem::factory()->{$state}()->make(['precio' => '185.50'])->toArray();
    $payload['codigo'] = 'FORGED';
    $payload['id'] = 9000;

    $response = $this->actingAs(catalogUser())->post(route('catalog.store'), $payload);

    $response->assertRedirect(route('catalog.index'))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('catalog_items', ['tipo' => $tipo, 'precio' => '185.50', 'nombre' => $payload['nombre']]);
    $item = CatalogItem::sole();
    expect($item->codigo)->toMatch('/^CAT-[0-9A-HJKMNP-TV-Z]{26}$/');
    expect($item->id)->not->toBe(9000);
    expect($item->precio)->toBe('185.50');
})->with([
    ['product', 'producto'], ['service', 'servicio'], ['sparePart', 'repuesto'],
]);

test('catalog generates different codes for consecutive records', function () {
    $items = CatalogItem::factory()->count(2)->create();

    expect($items[0]->codigo)->not->toBe($items[1]->codigo);
});

test('catalog updates and deactivates a record without changing its code', function () {
    $item = CatalogItem::factory()->create();
    $code = $item->codigo;
    $payload = $item->toArray();
    $payload['nombre'] = 'Extintor PQS ABC 6kg actualizado';
    $payload['precio'] = '199.90';
    $payload['activo'] = false;
    $payload['codigo'] = 'FORGED';

    $this->actingAs(catalogUser())->put(route('catalog.update', $item), $payload)
        ->assertRedirect(route('catalog.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('catalog_items', [
        'id' => $item->id, 'codigo' => $code, 'nombre' => $payload['nombre'],
        'precio' => '199.90', 'activo' => false,
    ]);
});

test('changing catalog type clears fields that no longer apply', function (string $from, string $to) {
    $item = CatalogItem::factory()->{$from}()->create();
    $payload = CatalogItem::factory()->{$to}()->make()->toArray();
    $payload['controla_stock'] = true;
    $payload['control_serializado'] = true;
    $payload['genera_barcode'] = true;
    $payload['tipo_tecnico'] = 'Planta';
    $payload['requiere_orden'] = true;
    $payload['requiere_certificado'] = true;
    $payload['checklist_aplicable'] = 'Revisión';

    $this->actingAs(catalogUser())->put(route('catalog.update', $item), $payload)
        ->assertSessionHasNoErrors()->assertRedirect(route('catalog.index'));

    $item->refresh();
    if ($item->tipo !== 'producto') {
        expect($item->controla_stock)->toBeFalse();
        expect($item->control_serializado)->toBeFalse();
        expect($item->genera_barcode)->toBeFalse();
    }
    if ($item->tipo !== 'servicio') {
        expect($item->tipo_tecnico)->toBeNull();
        expect($item->requiere_orden)->toBeFalse();
        expect($item->requiere_certificado)->toBeFalse();
        expect($item->checklist_aplicable)->toBeNull();
    }
})->with([
    ['product', 'service'], ['service', 'product'], ['service', 'sparePart'],
]);

test('catalog requires common fields', function () {
    $this->actingAs(catalogUser())->post(route('catalog.store'), [])
        ->assertSessionHasErrors(['tipo', 'categoria', 'nombre', 'unidad', 'precio', 'aplica_igv', 'activo']);

    $this->assertDatabaseCount('catalog_items', 0);
});

test('catalog rejects invalid values without saving', function (string $field, mixed $value, string $message) {
    $payload = CatalogItem::factory()->service()->make()->toArray();
    $payload[$field] = $value;

    $this->actingAs(catalogUser())->post(route('catalog.store'), $payload)
        ->assertSessionHasErrors([$field => $message]);

    $this->assertDatabaseCount('catalog_items', 0);
})->with([
    'invalid type' => ['tipo', 'inventario', 'Selecciona producto, servicio o repuesto.'],
    'negative price' => ['precio', '-1.00', 'El precio no puede ser negativo.'],
    'precision' => ['precio', '1.234', 'El precio debe tener como máximo dos decimales.'],
    'overflow' => ['precio', '10000000000.00', 'El precio no puede superar 9999999999.99.'],
    'non numeric' => ['precio', 'gratis', 'El precio debe ser un número.'],
    'missing technician' => ['tipo_tecnico', '', 'El campo tipo de técnico es obligatorio.'],
    'invalid boolean' => ['activo', 'yes', 'Selecciona una opción válida para activo.'],
    'long name' => ['nombre', str_repeat('a', 256), 'El campo nombre no puede superar 255 caracteres.'],
    'long category' => ['categoria', str_repeat('a', 101), 'El campo categoría no puede superar 100 caracteres.'],
    'long unit' => ['unidad', str_repeat('a', 31), 'El campo unidad no puede superar 30 caracteres.'],
    'long description' => ['descripcion', str_repeat('a', 5001), 'El campo descripción no puede superar 5000 caracteres.'],
    'long checklist' => ['checklist_aplicable', str_repeat('a', 256), 'El campo checklist aplicable no puede superar 255 caracteres.'],
]);

test('catalog update rejects invalid prices and preserves the original record', function () {
    $item = CatalogItem::factory()->create(['precio' => '120.00']);

    $this->actingAs(catalogUser())->put(route('catalog.update', $item), [...$item->toArray(), 'precio' => '-1'])
        ->assertSessionHasErrors('precio');

    $this->assertDatabaseHas('catalog_items', ['id' => $item->id, 'precio' => '120.00']);
});

test('catalog returns not found for missing records', function () {
    $this->actingAs(catalogUser())->get(route('catalog.edit', 999))->assertNotFound();
});

test('catalog permissions seeder is additive and idempotent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $role = Role::findByName('Administrador');
    $originalPermissions = $role->permissions->pluck('name')->all();

    $this->seed(CatalogPermissionsSeeder::class);
    $this->seed(CatalogPermissionsSeeder::class);

    expect($role->fresh()->permissions->pluck('name')->all())->toContain(...$originalPermissions);
    expect($role->fresh()->hasAllPermissions(['catalog.view', 'catalog.create', 'catalog.update']))->toBeTrue();
    expect(Permission::where('name', 'like', 'catalog.%')->count())->toBe(3);
    expect(Role::findByName('Vendedor')->hasPermissionTo('catalog.view'))->toBeTrue();
    expect(Role::findByName('Vendedor')->hasPermissionTo('catalog.update'))->toBeFalse();
    expect(Role::findByName('Almacén')->hasPermissionTo('catalog.view'))->toBeTrue();
});
