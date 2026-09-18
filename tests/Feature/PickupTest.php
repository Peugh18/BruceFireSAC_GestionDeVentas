<?php

use App\Models\Client;
use App\Models\ClientSite;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderPickup;
use App\Models\User;
use Database\Seeders\PickupPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(PickupPermissionsSeeder::class);
});

function pickupUser(array $permissions = []): User
{
    $user = User::factory()->create();
    if (! empty($permissions)) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

test('guests cannot access pickup endpoints', function () {
    $pickup = ServiceOrderPickup::factory()->create();

    $this->get(route('pickups.index'))->assertRedirect(route('login'));
    $this->get(route('pickups.create'))->assertRedirect(route('login'));
    $this->get(route('pickups.show', $pickup))->assertRedirect(route('login'));
    $this->post(route('pickups.store'))->assertRedirect(route('login'));
    $this->post(route('pickups.custody.update', $pickup))->assertRedirect(route('login'));
});

test('technician can create a pickup with photos and client signature details', function () {
    Storage::fake('public');

    $technician = pickupUser(['pickups.create', 'pickups.view']);
    $client = Client::factory()->create();
    $site = ClientSite::factory()->for($client)->create();
    $order = ServiceOrder::factory()->create([
        'client_id' => $client->id,
        'client_site_id' => $site->id,
    ]);

    $photo = UploadedFile::fake()->image('extintor.jpg');

    $response = $this->actingAs($technician)->post(route('pickups.store'), [
        'service_order_id' => $order->id,
        'client_site_id' => $site->id,
        'contacto' => 'Juan Perez (Administrador)',
        'fecha_hora_recojo' => '2026-09-18 10:00:00',
        'cantidad' => 5,
        'observaciones' => 'Equipos con abolladura menor en manómetro',
        'conforme_nombre' => 'Carlos Ruiz',
        'conforme_dni' => '45678912',
        'fotos' => [$photo],
    ]);

    $pickup = ServiceOrderPickup::where('service_order_id', $order->id)->first();
    expect($pickup)->not->toBeNull();

    $response->assertRedirect(route('pickups.show', $pickup));

    $this->assertDatabaseHas('service_order_pickups', [
        'id' => $pickup->id,
        'service_order_id' => $order->id,
        'client_id' => $client->id,
        'cantidad' => 5,
        'recogido_por_user_id' => $technician->id,
        'conforme_nombre' => 'Carlos Ruiz',
    ]);

    expect($pickup->getMedia('fotos'))->toHaveCount(1);
});

test('custody chain can be advanced sequentially', function () {
    $technician = pickupUser(['pickups.custody', 'pickups.view']);
    $plantSupervisor = pickupUser(['pickups.custody', 'pickups.view']);

    $pickup = ServiceOrderPickup::factory()->create([
        'recogido_por_user_id' => $technician->id,
        'recogido_en' => now(),
    ]);

    // Step 1: Recepción en Planta
    $this->actingAs($plantSupervisor)
        ->post(route('pickups.custody.update', $pickup), [
            'step' => 'recibido_planta',
        ])
        ->assertRedirect();

    $pickup->refresh();
    expect($pickup->recibido_planta_user_id)->toBe($plantSupervisor->id)
        ->and($pickup->recibido_planta_en)->not->toBeNull();

    // Step 2: Despachado / Entregado
    $this->actingAs($plantSupervisor)
        ->post(route('pickups.custody.update', $pickup), [
            'step' => 'entregado',
        ])
        ->assertRedirect();

    $pickup->refresh();
    expect($pickup->entregado_por_user_id)->toBe($plantSupervisor->id)
        ->and($pickup->entregado_en)->not->toBeNull();

    // Step 3: Conforme Cliente
    $this->actingAs($technician)
        ->post(route('pickups.custody.update', $pickup), [
            'step' => 'recibido_cliente',
            'extra_info' => 'Ing. Maria Torres',
        ])
        ->assertRedirect();

    $pickup->refresh();
    expect($pickup->recibido_cliente_por_user_id)->toBe($technician->id)
        ->and($pickup->recibido_cliente_en)->not->toBeNull()
        ->and($pickup->recibido_cliente_nombre)->toBe('Ing. Maria Torres');
});
