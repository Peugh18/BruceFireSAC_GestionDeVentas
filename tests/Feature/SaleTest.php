<?php

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
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
    $catalogItem = CatalogItem::factory()->create(['precio' => 150.00]);

    $payload = [
        'client_id' => $client->id,
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
    $catalogItem = CatalogItem::factory()->create(['precio' => 500.00]);

    $payload = [
        'client_id' => $client->id,
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
