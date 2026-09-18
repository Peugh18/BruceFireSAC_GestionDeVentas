<?php

use App\Models\Client;
use App\Models\Equipment;
use App\Models\ServiceOrder;
use App\Models\User;
use Database\Seeders\PickupPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(PickupPermissionsSeeder::class);
});

test('guests cannot access service order acta endpoint', function () {
    $order = ServiceOrder::factory()->create();

    $this->get(route('service-orders.acta.show', $order))->assertRedirect(route('login'));
});

test('user can view acta de conformidad with dynamic equipment list', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['service_orders.view', 'actas.view']);

    $client = Client::factory()->create();
    $order = ServiceOrder::factory()->create([
        'client_id' => $client->id,
    ]);

    $equipment = Equipment::factory()->count(3)->for($client)->create();
    $order->equipment()->attach($equipment->pluck('id'));

    $this->actingAs($user)
        ->get(route('service-orders.acta.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('service-orders/acta')
            ->has('serviceOrder', fn (Assert $so) => $so
                ->where('id', $order->id)
                ->where('codigo', $order->codigo)
                ->has('equipment', 3)
                ->etc()
            )
        );
});
