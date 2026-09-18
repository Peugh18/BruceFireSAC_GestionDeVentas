<?php

use App\Models\Deficiency;
use App\Models\Equipment;
use App\Models\EquipmentEvent;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function deficiencyUser(array $permissions = ['deficiencies.view', 'deficiencies.create', 'deficiencies.authorize', 'deficiencies.resolve']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('deficiency routes redirect guests to login', function () {
    $this->get('/deficiencies')->assertRedirect(route('login'));
});

test('deficiency routes forbid users without permissions', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/deficiencies')->assertForbidden();
});

test('user with permission can view deficiencies list', function () {
    $user = deficiencyUser();
    $deficiency = Deficiency::factory()->create();

    $this->actingAs($user)
        ->get(route('deficiencies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('deficiencies/index')
            ->has('deficiencies.data', 1)
            ->where('deficiencies.data.0.id', $deficiency->id)
        );
});

test('deficiency status transitions follow valid engine', function () {
    $user = deficiencyUser();
    $deficiency = Deficiency::factory()->create(['estado' => 'detectada']);

    // Valid transition to esperando_autorizacion
    $this->actingAs($user)
        ->patch(route('deficiencies.status', $deficiency->id), ['estado' => 'esperando_autorizacion'])
        ->assertRedirect();

    expect($deficiency->fresh()->estado)->toBe('esperando_autorizacion');

    // Valid transition to autorizada
    $this->actingAs($user)
        ->patch(route('deficiencies.status', $deficiency->id), ['estado' => 'autorizada'])
        ->assertRedirect();

    expect($deficiency->fresh()->estado)->toBe('autorizada');

    // Valid transition to en_correccion
    $this->actingAs($user)
        ->patch(route('deficiencies.status', $deficiency->id), ['estado' => 'en_correccion'])
        ->assertRedirect();

    expect($deficiency->fresh()->estado)->toBe('en_correccion');
});

test('invalid deficiency status transition is rejected', function () {
    $user = deficiencyUser();
    $deficiency = Deficiency::factory()->create(['estado' => 'detectada']);

    // Invalid direct transition to resuelta
    $this->actingAs($user)
        ->patch(route('deficiencies.status', $deficiency->id), ['estado' => 'resuelta'])
        ->assertSessionHasErrors('estado');

    expect($deficiency->fresh()->estado)->toBe('detectada');
});

test('resolving a deficiency registers an EquipmentEvent on equipment timeline', function () {
    $user = deficiencyUser();
    $equipment = Equipment::factory()->create();
    $deficiency = Deficiency::factory()->create([
        'equipment_id' => $equipment->id,
        'componente' => 'manguera',
        'estado' => 'autorizada',
    ]);

    $this->actingAs($user)
        ->patch(route('deficiencies.status', $deficiency->id), [
            'estado' => 'resuelta',
            'resolucion' => 'Se cambió la manguera por un repuesto original de 6kg.',
        ])
        ->assertRedirect();

    expect($deficiency->fresh()->estado)->toBe('resuelta');
    expect($deficiency->fresh()->resuelto_por_user_id)->toBe($user->id);

    // Verify EquipmentEvent created
    $this->assertDatabaseHas('equipment_events', [
        'equipment_id' => $equipment->id,
        'tipo' => 'deficiencia_resuelta',
        'user_id' => $user->id,
    ]);

    $event = EquipmentEvent::where('equipment_id', $equipment->id)->where('tipo', 'deficiencia_resuelta')->first();
    expect($event->descripcion)->toContain('manguera');
    expect($event->descripcion)->toContain('Se cambió la manguera por un repuesto original');
});
