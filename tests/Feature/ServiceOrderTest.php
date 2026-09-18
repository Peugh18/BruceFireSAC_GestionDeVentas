<?php

use App\Models\Client;
use App\Models\ClientSite;
use App\Models\Equipment;
use App\Models\EquipmentEvent;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function serviceOrderUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/** @return array<string, mixed> */
function serviceOrderPayload(Client $client, array $equipmentIds): array
{
    return [
        'client_id' => $client->id,
        'tipo_servicio' => 'recarga',
        'fecha' => '2026-09-18',
        'prioridad' => 'alta',
        'equipment_ids' => $equipmentIds,
    ];
}

test('guests cannot access or mutate service orders', function () {
    $order = ServiceOrder::factory()->create();

    $this->get(route('service-orders.index'))->assertRedirect(route('login'));
    $this->get(route('service-orders.create'))->assertRedirect(route('login'));
    $this->get(route('service-orders.show', $order))->assertRedirect(route('login'));
    $this->post(route('service-orders.store'))->assertRedirect(route('login'));
    $this->patch(route('service-orders.status', $order), ['estado' => 'recibido_planta'])->assertRedirect(route('login'));
    $this->patch(route('service-orders.update', $order), ['tecnico_user_id' => null])->assertRedirect(route('login'));

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'estado' => 'pendiente_recepcion']);
    $this->assertDatabaseCount('service_order_status_histories', 1);
});

test('a seller creates an order with its equipment and initial history', function () {
    $seller = serviceOrderUser('Vendedor');
    $client = Client::factory()->create();
    $site = ClientSite::factory()->for($client)->create(['activo' => true]);
    $vehicle = Vehicle::factory()->for($client)->create(['activo' => true]);
    $equipment = Equipment::factory()->count(2)->for($client)->create([
        'client_site_id' => $site->id,
        'vehicle_id' => $vehicle->id,
    ]);

    $response = $this->actingAs($seller)->post(route('service-orders.store'), [
        ...serviceOrderPayload($client, $equipment->modelKeys()),
        'client_site_id' => $site->id,
        'vehicle_id' => $vehicle->id,
        'observaciones' => 'Recarga anual.',
    ]);

    $order = ServiceOrder::sole();
    $response->assertRedirect(route('service-orders.show', $order));
    expect($order->codigo)->toStartWith('OS-');
    $this->assertDatabaseHas('service_orders', [
        'id' => $order->id, 'client_id' => $client->id, 'client_site_id' => $site->id,
        'vehicle_id' => $vehicle->id, 'estado' => 'pendiente_recepcion', 'sale_id' => null,
        'fecha' => '2026-09-18', 'tipo_servicio' => 'recarga', 'prioridad' => 'alta',
    ]);
    foreach ($equipment as $item) {
        $this->assertDatabaseHas('service_order_equipment', ['service_order_id' => $order->id, 'equipment_id' => $item->id]);
    }
    $this->assertDatabaseHas('service_order_status_histories', [
        'service_order_id' => $order->id, 'estado_anterior' => null,
        'estado' => 'pendiente_recepcion', 'user_id' => $seller->id,
    ]);
});

test('orders keep a nullable future sale reference and generate different codes', function () {
    $orders = ServiceOrder::factory()->count(2)->create(['sale_id' => 42]);

    expect($orders[0]->codigo)->not->toBe($orders[1]->codigo);
    $this->assertDatabaseHas('service_orders', ['id' => $orders[0]->id, 'sale_id' => 42]);
});

test('creation renders only equipment for the chosen client', function () {
    $seller = serviceOrderUser('Vendedor');
    $client = Client::factory()->create();
    $equipment = Equipment::factory()->for($client)->create();
    Equipment::factory()->create();

    $this->actingAs($seller)->get(route('service-orders.create', ['client_id' => $client->id]))
        ->assertInertia(fn (Assert $page) => $page->component('service-orders/create')
            ->has('equipment', 1)->where('equipment.0.id', $equipment->id)
            ->where('defaultClientId', $client->id)->where('canAssign', false));

    $this->get(route('service-orders.create'))
        ->assertInertia(fn (Assert $page) => $page->has('equipment', 0));
});

test('creation rejects missing required fields', function () {
    $this->actingAs(serviceOrderUser('Vendedor'))->post(route('service-orders.store'), [])
        ->assertInvalid(['client_id', 'tipo_servicio', 'fecha', 'prioridad', 'equipment_ids' => 'Selecciona al menos un equipo.']);

    $this->assertDatabaseCount('service_orders', 0);
    $this->assertDatabaseCount('service_order_status_histories', 0);
});

test('creation rejects foreign client associations', function (string $field) {
    $client = Client::factory()->create();
    $equipment = Equipment::factory()->for($client)->create();
    $foreignValue = match ($field) {
        'client_site_id' => ClientSite::factory()->create()->id,
        'vehicle_id' => Vehicle::factory()->create()->id,
        'equipment_ids' => [Equipment::factory()->create()->id],
    };
    $errorKey = $field === 'equipment_ids' ? 'equipment_ids.0' : $field;

    $this->actingAs(serviceOrderUser('Vendedor'))->post(route('service-orders.store'), [
        ...serviceOrderPayload($client, [$equipment->id]), $field => $foreignValue,
    ])->assertInvalid([$errorKey]);

    $this->assertDatabaseCount('service_orders', 0);
    $this->assertDatabaseCount('service_order_equipment', 0);
})->with(['client_site_id', 'vehicle_id', 'equipment_ids']);

test('creation rejects equipment outside the selected site or vehicle', function (string $field) {
    $client = Client::factory()->create();
    $equipment = Equipment::factory()->for($client)->create();
    $locationId = $field === 'client_site_id'
        ? ClientSite::factory()->for($client)->create(['activo' => true])->id
        : Vehicle::factory()->for($client)->create(['activo' => true])->id;

    $this->actingAs(serviceOrderUser('Vendedor'))->post(route('service-orders.store'), [
        ...serviceOrderPayload($client, [$equipment->id]), $field => $locationId,
    ])->assertInvalid(['equipment_ids.0' => 'Cada equipo debe pertenecer al cliente']);

    $this->assertDatabaseCount('service_orders', 0);
})->with(['client_site_id', 'vehicle_id']);

test('creation rejects duplicate equipment and unsupported input', function (string $field, mixed $value, string $errorKey) {
    $client = Client::factory()->create();
    $equipment = Equipment::factory()->for($client)->create();
    $payload = serviceOrderPayload($client, [$equipment->id]);
    $payload[$field] = $field === 'equipment_ids' ? [$equipment->id, $equipment->id] : $value;

    $this->actingAs(serviceOrderUser('Vendedor'))->post(route('service-orders.store'), $payload)
        ->assertInvalid([$errorKey]);

    $this->assertDatabaseCount('service_orders', 0);
    $this->assertDatabaseCount('service_order_status_histories', 0);
})->with([
    'duplicate equipment' => ['equipment_ids', null, 'equipment_ids.0'],
    'unknown service' => ['tipo_servicio', 'inexistente', 'tipo_servicio'],
    'unknown priority' => ['prioridad', 'urgente', 'prioridad'],
    'invalid date' => ['fecha', '2026-02-30', 'fecha'],
    'injected status' => ['estado', 'cerrado', 'estado'],
    'injected code' => ['codigo', 'OS-MANUAL', 'codigo'],
    'unverified sale' => ['sale_id', 12, 'sale_id'],
    'injected price' => ['precio', 200, 'precio'],
]);

test('inactive clients cannot receive new service orders', function () {
    $client = Client::factory()->create(['activo' => false]);
    $equipment = Equipment::factory()->for($client)->create();

    $this->actingAs(serviceOrderUser('Vendedor'))->post(route('service-orders.store'), serviceOrderPayload($client, [$equipment->id]))
        ->assertInvalid(['client_id' => 'Selecciona un cliente activo.']);

    $this->assertDatabaseCount('service_orders', 0);
});

test('a plant technician cannot create orders', function () {
    $client = Client::factory()->create();
    $equipment = Equipment::factory()->for($client)->create();

    $this->actingAs(serviceOrderUser('Técnico de Planta'))->post(route('service-orders.store'), serviceOrderPayload($client, [$equipment->id]))
        ->assertForbidden();

    $this->assertDatabaseCount('service_orders', 0);
});

test('the complete ordered lifecycle records history and relevant equipment events', function () {
    $this->freezeTime();
    $technician = serviceOrderUser('Técnico de Planta');
    $seller = serviceOrderUser('Vendedor');
    $order = ServiceOrder::factory()->withEquipment()->create();

    foreach ([
        'recibido_planta', 'en_revision', 'esperando_autorizacion', 'autorizado',
        'en_proceso', 'trabajo_terminado', 'pendiente_datos', 'datos_completos',
        'listo_certificado', 'listo_entrega', 'entregado', 'cerrado',
    ] as $status) {
        $actor = $status === 'autorizado' ? $seller : $technician;
        $this->actingAs($actor)->patch(route('service-orders.status', $order), [
            'estado' => $status, 'observaciones' => 'Avance verificado.',
        ])->assertRedirect(route('service-orders.show', $order));

        $this->assertDatabaseHas('service_order_status_histories', [
            'service_order_id' => $order->id, 'estado' => $status,
            'user_id' => $actor->id, 'observaciones' => 'Avance verificado.',
        ]);
    }

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'estado' => 'cerrado']);
    $this->assertDatabaseCount('service_order_status_histories', 13);
    $this->assertDatabaseCount('equipment_events', 8);
    foreach ($order->equipment as $equipment) {
        foreach (['Recibido en Planta' => $technician, 'Autorizado' => $seller, 'Cerrado' => $technician] as $label => $actor) {
            $this->assertDatabaseHas('equipment_events', [
                'equipment_id' => $equipment->id, 'tipo' => 'cambio_estado',
                'descripcion' => "Orden {$order->codigo}: {$label}.",
                'user_id' => $actor->id, 'fecha' => now()->toDateString(),
            ]);
        }
    }
});

test('invalid transitions leave the order history and equipment unchanged', function (string $current, string $next) {
    $order = ServiceOrder::factory()->withEquipment(1)->create(['estado' => $current]);

    $this->actingAs(serviceOrderUser('Técnico de Planta'))
        ->patch(route('service-orders.status', $order), ['estado' => $next])
        ->assertInvalid(['estado' => 'La transición solicitada no es válida para el estado actual de la orden.']);

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'estado' => $current]);
    $this->assertDatabaseCount('service_order_status_histories', 1);
    $this->assertDatabaseCount('equipment_events', 1);
})->with([
    'skip reception' => ['pendiente_recepcion', 'en_proceso'],
    'backwards' => ['en_revision', 'recibido_planta'],
    'repeat' => ['recibido_planta', 'recibido_planta'],
    'reopen closed order' => ['cerrado', 'recibido_planta'],
    'unknown state' => ['pendiente_recepcion', 'cancelado'],
]);

test('sellers cannot execute and technicians cannot grant authorization', function (string $role, string $current, string $next) {
    $order = ServiceOrder::factory()->withEquipment(1)->create(['estado' => $current]);

    $this->actingAs(serviceOrderUser($role))->patch(route('service-orders.status', $order), ['estado' => $next])
        ->assertForbidden();

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'estado' => $current]);
    $this->assertDatabaseCount('service_order_status_histories', 1);
    $this->assertDatabaseCount('equipment_events', 1);
})->with([
    'seller cannot receive' => ['Vendedor', 'pendiente_recepcion', 'recibido_planta'],
    'seller cannot execute' => ['Vendedor', 'autorizado', 'en_proceso'],
    'seller cannot close' => ['Vendedor', 'entregado', 'cerrado'],
    'technician cannot authorize' => ['Técnico de Planta', 'esperando_autorizacion', 'autorizado'],
]);

test('an equipment event failure rolls back the status and its history', function () {
    $technician = serviceOrderUser('Técnico de Planta');
    $order = ServiceOrder::factory()->withEquipment(2)->create();
    $attempts = 0;
    Event::listen('eloquent.creating: '.EquipmentEvent::class, function () use (&$attempts): void {
        $attempts++;
        if ($attempts === 2) {
            throw new RuntimeException('Event could not be saved.');
        }
    });

    expect(fn () => $order->transitionTo('recibido_planta', $technician))
        ->toThrow(RuntimeException::class, 'Event could not be saved.');

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'estado' => 'pendiente_recepcion']);
    $this->assertDatabaseCount('service_order_status_histories', 1);
    $this->assertDatabaseCount('equipment_events', 2);
});

test('a stale model cannot repeat an already committed transition', function () {
    $technician = serviceOrderUser('Técnico de Planta');
    $order = ServiceOrder::factory()->withEquipment(1)->create();
    $staleOrder = ServiceOrder::findOrFail($order->id);
    $order->transitionTo('recibido_planta', $technician);

    expect(fn () => $staleOrder->transitionTo('recibido_planta', $technician))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'estado' => 'recibido_planta']);
    $this->assertDatabaseCount('service_order_status_histories', 2);
    $this->assertDatabaseCount('equipment_events', 2);
});

test('direct model status edits cannot bypass the transition history', function () {
    $order = ServiceOrder::factory()->create();
    $order->estado = 'recibido_planta';

    expect(fn () => $order->save())->toThrow(ValidationException::class);

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'estado' => 'pendiente_recepcion']);
    $this->assertDatabaseCount('service_order_status_histories', 1);
});

test('order profiles expose history and only transitions permitted for the viewer', function () {
    $order = ServiceOrder::factory()->withEquipment(1)->create();

    $this->actingAs(serviceOrderUser('Técnico de Planta'))->get(route('service-orders.show', $order))
        ->assertInertia(fn (Assert $page) => $page->component('service-orders/show')
            ->has('order.equipment', 1)->has('order.status_history', 1)
            ->where('transitions', ['recibido_planta'])->missing('order.precio'));

    $this->actingAs(serviceOrderUser('Vendedor'))->get(route('service-orders.show', $order))
        ->assertInertia(fn (Assert $page) => $page->where('transitions', []));
});

test('a plant technician cannot inject prices during a status change', function () {
    $order = ServiceOrder::factory()->create(['estado' => 'autorizado']);

    $this->actingAs(serviceOrderUser('Técnico de Planta'))->patch(route('service-orders.status', $order), [
        'estado' => 'en_proceso', 'precio' => 10,
    ])->assertInvalid(['precio' => 'Las órdenes de servicio no gestionan precios.']);

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'estado' => 'autorizado']);
});

test('assignment requires its own permission even during creation', function () {
    $seller = serviceOrderUser('Vendedor');
    $technician = serviceOrderUser('Técnico de Planta');
    $order = ServiceOrder::factory()->withEquipment(1)->create();

    $this->actingAs($seller)->patch(route('service-orders.update', $order), ['tecnico_user_id' => $technician->id])
        ->assertForbidden();
    $this->post(route('service-orders.store'), [
        ...serviceOrderPayload($order->client, $order->equipment->modelKeys()),
        'tecnico_user_id' => $technician->id,
    ])->assertInvalid(['tecnico_user_id' => 'No tienes permiso para asignar técnicos.']);

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'tecnico_user_id' => null]);
    $this->assertDatabaseCount('service_orders', 1);
});

test('an authorized coordinator can assign and unassign an eligible technician', function () {
    $coordinator = serviceOrderUser('Vendedor');
    $coordinator->givePermissionTo('service_orders.assign');
    $technician = serviceOrderUser('Técnico de Planta');
    $order = ServiceOrder::factory()->create();

    $this->actingAs($coordinator)->patch(route('service-orders.update', $order), ['tecnico_user_id' => $technician->id])
        ->assertRedirect(route('service-orders.show', $order));

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'tecnico_user_id' => $technician->id, 'estado' => 'pendiente_recepcion']);

    $this->patch(route('service-orders.update', $order), ['tecnico_user_id' => null])->assertRedirect();
    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'tecnico_user_id' => null]);
});

test('assignment rejects non technicians and closed orders', function () {
    $coordinator = serviceOrderUser('Vendedor');
    $coordinator->givePermissionTo('service_orders.assign');
    $order = ServiceOrder::factory()->create();
    $closedOrder = ServiceOrder::factory()->create(['estado' => 'cerrado']);

    $this->actingAs($coordinator)->patch(route('service-orders.update', $order), ['tecnico_user_id' => $coordinator->id])
        ->assertInvalid(['tecnico_user_id' => 'Selecciona un usuario con permiso para ejecutar servicios.']);
    $this->patch(route('service-orders.update', $closedOrder), ['tecnico_user_id' => null])->assertForbidden();

    $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'tecnico_user_id' => null]);
    $this->assertDatabaseHas('service_orders', ['id' => $closedOrder->id, 'estado' => 'cerrado']);
});

test('the list combines client search state and technician filters with pagination', function () {
    $technician = serviceOrderUser('Técnico de Planta');
    $client = Client::factory()->create(['razon_social' => 'Cliente de prueba filtros']);
    ServiceOrder::factory()->count(16)->for($client)->create(['estado' => 'en_proceso', 'tecnico_user_id' => $technician->id]);
    ServiceOrder::factory()->for($client)->create(['estado' => 'recibido_planta', 'tecnico_user_id' => $technician->id]);
    ServiceOrder::factory()->for($client)->create(['estado' => 'en_proceso']);
    ServiceOrder::factory()->create(['estado' => 'en_proceso', 'tecnico_user_id' => $technician->id]);
    $filters = ['search' => 'Cliente de prueba filtros', 'estado' => 'en_proceso', 'tecnico_user_id' => $technician->id];

    $this->actingAs($technician)->get(route('service-orders.index', $filters))
        ->assertInertia(fn (Assert $page) => $page->component('service-orders/index')
            ->has('orders.data', 15)->where('orders.total', 16)->where('orders.last_page', 2)
            ->where('can.create', false)
            ->where('orders.next_page_url', fn (string $url): bool => str_contains($url, 'estado=en_proceso') && str_contains($url, 'tecnico_user_id=')));

    $this->get(route('service-orders.index', [...$filters, 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('orders.data', 1)->where('orders.current_page', 2));
});

test('orders can be found by order code or equipment code', function (string $searchBy) {
    $order = ServiceOrder::factory()->withEquipment(1)->create();
    ServiceOrder::factory()->create();
    $search = $searchBy === 'order' ? $order->codigo : $order->equipment->first()->codigo;

    $this->actingAs(serviceOrderUser('Vendedor'))->get(route('service-orders.index', ['search' => $search]))
        ->assertInertia(fn (Assert $page) => $page->has('orders.data', 1)->where('orders.data.0.id', $order->id));
})->with(['order', 'equipment']);

test('users without view permission cannot read service orders', function () {
    $order = ServiceOrder::factory()->create();

    $this->actingAs(serviceOrderUser('Almacén'))->get(route('service-orders.index'))->assertForbidden();
    $this->get(route('service-orders.show', $order))->assertForbidden();
});

test('the policy respects the seeded service permissions', function (string $role, bool $view, bool $create, bool $receive, bool $execute, bool $close, bool $assign) {
    $user = serviceOrderUser($role);
    $pendingOrder = ServiceOrder::factory()->create();
    $authorizedOrder = ServiceOrder::factory()->create(['estado' => 'autorizado']);
    $deliveredOrder = ServiceOrder::factory()->create(['estado' => 'entregado']);

    expect($user->can('viewAny', ServiceOrder::class))->toBe($view);
    expect($user->can('create', ServiceOrder::class))->toBe($create);
    expect($user->can('transition', [$pendingOrder, 'recibido_planta']))->toBe($receive);
    expect($user->can('transition', [$authorizedOrder, 'en_proceso']))->toBe($execute);
    expect($user->can('transition', [$deliveredOrder, 'cerrado']))->toBe($close);
    expect($user->can('assign', $pendingOrder))->toBe($assign);
})->with([
    'seller' => ['Vendedor', true, true, false, false, false, false],
    'plant technician' => ['Técnico de Planta', true, false, true, true, true, false],
    'field technician' => ['Técnico de Campo', true, false, false, true, true, false],
    'manager' => ['Gerente', true, true, true, true, true, true],
    'warehouse' => ['Almacén', false, false, false, false, false, false],
]);
