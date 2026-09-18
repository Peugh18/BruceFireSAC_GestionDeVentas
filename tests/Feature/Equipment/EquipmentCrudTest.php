<?php

use App\Models\Client;
use App\Models\Equipment;
use App\Models\User;
use Database\Seeders\EquipmentPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(EquipmentPermissionsSeeder::class);
});

function equipmentUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('guests are redirected to the login page', function () {
    $this->get(route('equipment.index'))->assertRedirect(route('login'));
});

test('a user with equipment.view can list equipment', function () {
    $user = equipmentUserWithRole('Vendedor');
    Equipment::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('equipment.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('equipment/index')
            ->has('equipment.data', 3)
        );
});

test('a user without equipment.view cannot list equipment', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('equipment.index'))
        ->assertForbidden();
});

test('warehouse role can list equipment read-only', function () {
    $user = equipmentUserWithRole('Almacén');
    Equipment::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('equipment.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('equipment/index')
            ->has('equipment.data', 2)
        );

    $this->actingAs($user)
        ->post(route('equipment.store'), [
            'origen' => 'desconocido',
            'tipo_equipo' => 'Extintor',
        ])
        ->assertForbidden();
});

test('a user with equipment.create can register equipment with an auto-generated code', function () {
    $user = equipmentUserWithRole('Vendedor');
    $client = Client::factory()->create();

    $response = $this->actingAs($user)->post(route('equipment.store'), [
        'client_id' => $client->id,
        'origen' => 'externo',
        'tipo_equipo' => 'Extintor PQS',
        'agente' => 'PQS ABC',
        'capacidad' => '6 kg',
        'marca' => 'Amerex',
        'serie_fabricante' => 'SN-12345',
        'anio_fabricacion' => '2022',
    ]);

    $equipment = Equipment::first();

    expect($equipment)->not->toBeNull();
    expect($equipment->codigo)->toBe('BF-EQ-000001');
    expect($equipment->barcode)->toBe('BF-EQ-000001');
    expect($equipment->client_id)->toBe($client->id);

    $response->assertRedirect(route('equipment.show', $equipment));
});

test('alta tecnica rapida allows unreadable fields to be left blank', function () {
    $user = equipmentUserWithRole('Técnico de Planta');
    $client = Client::factory()->create();

    $this->actingAs($user)->post(route('equipment.store'), [
        'client_id' => $client->id,
        'origen' => 'desconocido',
        'tipo_equipo' => 'Extintor',
    ])->assertRedirect();

    $equipment = Equipment::first();

    expect($equipment)->not->toBeNull();
    expect($equipment->marca)->toBeNull();
    expect($equipment->serie_fabricante)->toBeNull();
    expect($equipment->anio_fabricacion)->toBeNull();
});

test('registering equipment creates an alta event', function () {
    $user = equipmentUserWithRole('Vendedor');
    $client = Client::factory()->create();

    $this->actingAs($user)->post(route('equipment.store'), [
        'client_id' => $client->id,
        'origen' => 'desconocido',
        'tipo_equipo' => 'Extintor',
    ]);

    $equipment = Equipment::first();

    expect($equipment->events()->where('tipo', 'alta')->count())->toBe(1);
});

test('a user without equipment.create cannot register equipment', function () {
    $user = equipmentUserWithRole('Gerente');
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->post(route('equipment.store'), [
            'client_id' => $client->id,
            'origen' => 'desconocido',
            'tipo_equipo' => 'Extintor',
        ])
        ->assertForbidden();

    expect(Equipment::count())->toBe(0);
});

test('a user with equipment.update can update equipment details', function () {
    $user = equipmentUserWithRole('Vendedor');
    $equipment = Equipment::factory()->create(['estado' => 'activo']);

    $this->actingAs($user)
        ->put(route('equipment.update', $equipment), [
            'origen' => $equipment->origen,
            'tipo_equipo' => $equipment->tipo_equipo,
            'estado' => 'fuera_de_servicio',
        ])
        ->assertRedirect(route('equipment.show', $equipment));

    expect($equipment->fresh()->estado)->toBe('fuera_de_servicio');
});

test('a user without equipment.update cannot update equipment', function () {
    $user = equipmentUserWithRole('Gerente');
    $equipment = Equipment::factory()->create();

    $this->actingAs($user)
        ->put(route('equipment.update', $equipment), [
            'origen' => $equipment->origen,
            'tipo_equipo' => $equipment->tipo_equipo,
            'estado' => 'retirado',
        ])
        ->assertForbidden();
});

test('equipment cannot be deleted even when it has history', function () {
    $equipment = Equipment::factory()->create();

    expect(Route::has('equipment.destroy'))->toBeFalse();
    expect(Equipment::count())->toBe(1);
});

test('the client equipment endpoint returns only that client\'s equipment', function () {
    $user = equipmentUserWithRole('Vendedor');
    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();

    Equipment::factory()->count(2)->create(['client_id' => $clientA->id]);
    Equipment::factory()->create(['client_id' => $clientB->id]);

    $response = $this->actingAs($user)->getJson(route('clients.equipment.index', $clientA));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('the equipment profile shows its transfers and events', function () {
    $user = equipmentUserWithRole('Vendedor');
    $equipment = Equipment::factory()->create();

    $this->actingAs($user)
        ->get(route('equipment.show', $equipment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('equipment/show')
            ->has('equipment.events', 1)
        );
});
