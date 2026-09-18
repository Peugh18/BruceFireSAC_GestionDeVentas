<?php

use App\Models\Client;
use App\Models\ClientSite;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('guests are redirected to the login page', function () {
    $this->get(route('clients.index'))->assertRedirect(route('login'));
});

test('a user with clients.view can list clients', function () {
    $user = userWithRole('Vendedor');
    Client::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clients/index')
            ->has('clients.data', 3)
        );
});

test('a user without clients.view cannot list clients', function () {
    $user = userWithRole('Almacén');

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertForbidden();
});

test('the client list can be filtered by search term', function () {
    $user = userWithRole('Vendedor');
    Client::factory()->create(['razon_social' => 'Fonpell SAC']);
    Client::factory()->create(['razon_social' => 'Otro Cliente SAC']);

    $this->actingAs($user)
        ->get(route('clients.index', ['search' => 'Fonpell']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.razon_social', 'Fonpell SAC')
        );
});

test('a user with clients.create can create a client with an auto-generated code', function () {
    $user = userWithRole('Vendedor');

    $response = $this->actingAs($user)->post(route('clients.store'), [
        'tipo_documento' => 'ruc',
        'numero_documento' => '20123456789',
        'razon_social' => 'Fonpell SAC',
        'nombre_comercial' => 'Fonpell',
        'telefono' => '014567890',
        'whatsapp' => '987654321',
        'email' => 'contacto@fonpell.pe',
        'direccion_fiscal' => 'Av. Siempre Viva 123',
        'departamento' => 'Lima',
        'provincia' => 'Lima',
        'distrito' => 'Miraflores',
        'ubigeo' => '150122',
        'activo' => true,
        'observaciones' => null,
    ]);

    $client = Client::firstWhere('razon_social', 'Fonpell SAC');

    expect($client)->not->toBeNull();
    expect($client->codigo)->toBe('CLI-000001');

    $response->assertRedirect(route('clients.show', $client));
});

test('a user without clients.create cannot create a client', function () {
    $user = userWithRole('Gerente');

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'tipo_documento' => 'ruc',
            'numero_documento' => '20123456789',
            'razon_social' => 'Fonpell SAC',
        ])
        ->assertForbidden();

    expect(Client::count())->toBe(0);
});

test('creating a client requires the mandatory fields', function () {
    $user = userWithRole('Vendedor');

    $this->actingAs($user)
        ->post(route('clients.store'), [])
        ->assertSessionHasErrors(['tipo_documento', 'numero_documento', 'razon_social']);
});

test('the numero_documento must be unique for the same tipo_documento', function () {
    $user = userWithRole('Vendedor');
    Client::factory()->create(['tipo_documento' => 'ruc', 'numero_documento' => '20123456789']);

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'tipo_documento' => 'ruc',
            'numero_documento' => '20123456789',
            'razon_social' => 'Otro Cliente SAC',
        ])
        ->assertSessionHasErrors(['numero_documento']);
});

test('a user with clients.update can update a client', function () {
    $user = userWithRole('Vendedor');
    $client = Client::factory()->create(['razon_social' => 'Nombre Original SAC']);

    $this->actingAs($user)
        ->put(route('clients.update', $client), [
            'tipo_documento' => $client->tipo_documento,
            'numero_documento' => $client->numero_documento,
            'razon_social' => 'Nombre Actualizado SAC',
            'activo' => false,
        ])
        ->assertRedirect(route('clients.show', $client));

    expect($client->fresh()->razon_social)->toBe('Nombre Actualizado SAC');
    expect($client->fresh()->activo)->toBeFalse();
});

test('a user without clients.update cannot update a client', function () {
    $user = userWithRole('Gerente');
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->put(route('clients.update', $client), [
            'tipo_documento' => $client->tipo_documento,
            'numero_documento' => $client->numero_documento,
            'razon_social' => 'Nombre Actualizado SAC',
        ])
        ->assertForbidden();
});

test('the client profile shows its sites and vehicles', function () {
    $user = userWithRole('Vendedor');
    $client = Client::factory()
        ->has(ClientSite::factory()->count(2), 'sites')
        ->has(Vehicle::factory()->count(1), 'vehicles')
        ->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clients/show')
            ->has('client.sites', 2)
            ->has('client.vehicles', 1)
        );
});

test('a user with clients.update can register a site for a client', function () {
    $user = userWithRole('Vendedor');
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->post(route('clients.sites.store', $client), [
            'tipo' => 'almacen',
            'nombre' => 'Almacen Central',
            'direccion' => 'Av. Industrial 456',
            'activo' => true,
        ])
        ->assertRedirect(route('clients.show', $client));

    expect($client->sites()->count())->toBe(1);
});

test('a user with clients.update can register a vehicle for a client', function () {
    $user = userWithRole('Vendedor');
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->post(route('clients.vehicles.store', $client), [
            'placa' => 'ABC-123',
            'marca' => 'Toyota',
            'activo' => true,
        ])
        ->assertRedirect(route('clients.show', $client));

    expect($client->vehicles()->count())->toBe(1);
});
