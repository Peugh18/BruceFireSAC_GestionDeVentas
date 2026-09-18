<?php

use App\Models\Equipment;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderChecklist;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function checklistUser(array $permissions = ['checklists.view', 'checklists.fill', 'service_orders.view', 'equipment.view']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('checklist routes redirect guests to login', function () {
    $this->get('/service-orders/1/equipment/1/checklist')->assertRedirect(route('login'));
    $this->post('/service-orders/1/equipment/1/checklist')->assertRedirect(route('login'));
});

test('checklist routes forbid users without permissions', function () {
    $user = User::factory()->create();
    $order = ServiceOrder::factory()->create();
    $equipment = Equipment::factory()->create();
    $order->equipment()->attach($equipment->id);

    $this->actingAs($user)
        ->get(route('checklists.show', [$order->id, $equipment->id]))
        ->assertForbidden();
});

test('user with permission can view mobile-first equipment checklist', function () {
    $user = checklistUser();
    $order = ServiceOrder::factory()->create();
    $equipment = Equipment::factory()->create();
    $order->equipment()->attach($equipment->id);

    $this->actingAs($user)
        ->get(route('checklists.show', [$order->id, $equipment->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checklists/show')
            ->where('serviceOrder.id', $order->id)
            ->where('equipment.id', $equipment->id)
            ->has('checklist.items')
        );
});

test('saving checklist with observed item automatically generates deficiency', function () {
    $user = checklistUser();
    $order = ServiceOrder::factory()->create();
    $equipment = Equipment::factory()->create();
    $order->equipment()->attach($equipment->id);

    $payload = [
        'estado' => 'completado',
        'items' => [
            [
                'componente' => 'manguera',
                'condicion' => 'observado',
                'nota' => 'Manguera rajada en el acople',
                'accion_recomendada' => 'Reemplazar manguera',
                'repuesto_sugerido' => 'Manguera PQS 6kg',
                'requiere_autorizacion' => true,
            ],
            [
                'componente' => 'valvula',
                'condicion' => 'conforme',
                'nota' => null,
            ],
        ],
    ];

    $this->actingAs($user)
        ->post(route('checklists.store', [$order->id, $equipment->id]), $payload)
        ->assertRedirect(route('service-orders.show', $order->id));

    $checklist = ServiceOrderChecklist::where('service_order_id', $order->id)
        ->where('equipment_id', $equipment->id)
        ->first();

    expect($checklist)->not->toBeNull();
    expect($checklist->estado)->toBe('completado');

    $this->assertDatabaseHas('checklist_items', [
        'checklist_id' => $checklist->id,
        'componente' => 'manguera',
        'condicion' => 'observado',
    ]);

    $this->assertDatabaseHas('deficiencies', [
        'service_order_id' => $order->id,
        'equipment_id' => $equipment->id,
        'componente' => 'manguera',
        'condicion' => 'observado',
        'nota' => 'Manguera rajada en el acople',
        'requiere_autorizacion' => true,
        'estado' => 'detectada',
    ]);
});
