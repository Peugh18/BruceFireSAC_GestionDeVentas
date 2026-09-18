<?php

use App\Models\Client;
use App\Models\Equipment;
use App\Models\User;
use Database\Seeders\EquipmentPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(EquipmentPermissionsSeeder::class);
});

function transferUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('a user with equipment.transfer can transfer equipment to another client', function () {
    $user = transferUserWithRole('Vendedor');
    $origen = Client::factory()->create();
    $destino = Client::factory()->create();
    $equipment = Equipment::factory()->create(['client_id' => $origen->id, 'client_site_id' => null]);

    $this->actingAs($user)
        ->post(route('equipment.transfer', $equipment), [
            'destino_client_id' => $destino->id,
            'fecha' => now()->toDateString(),
            'motivo' => 'Venta del equipo a otro cliente',
        ])
        ->assertRedirect(route('equipment.show', $equipment));

    $equipment->refresh();

    expect($equipment->client_id)->toBe($destino->id);
    expect($equipment->transfers()->count())->toBe(1);

    $transfer = $equipment->transfers()->first();
    expect($transfer->origen_client_id)->toBe($origen->id);
    expect($transfer->destino_client_id)->toBe($destino->id);
    expect($transfer->responsable_user_id)->toBe($user->id);
});

test('transferring equipment preserves the previous alta event history', function () {
    $user = transferUserWithRole('Vendedor');
    $origen = Client::factory()->create();
    $destino = Client::factory()->create();
    $equipment = Equipment::factory()->create(['client_id' => $origen->id]);

    $this->actingAs($user)->post(route('equipment.transfer', $equipment), [
        'destino_client_id' => $destino->id,
        'fecha' => now()->toDateString(),
        'motivo' => 'Cambio de sede',
    ]);

    $equipment->refresh();

    expect($equipment->events()->where('tipo', 'alta')->count())->toBe(1);
    expect($equipment->events()->where('tipo', 'transferencia')->count())->toBe(1);
    expect($equipment->events()->count())->toBe(2);
});

test('a user without equipment.transfer cannot transfer equipment', function () {
    $user = transferUserWithRole('Gerente');
    $destino = Client::factory()->create();
    $equipment = Equipment::factory()->create();

    $this->actingAs($user)
        ->post(route('equipment.transfer', $equipment), [
            'destino_client_id' => $destino->id,
            'fecha' => now()->toDateString(),
            'motivo' => 'Cambio de sede',
        ])
        ->assertForbidden();

    expect($equipment->fresh()->transfers()->count())->toBe(0);
});
