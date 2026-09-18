<?php

use App\Models\Deficiency;
use App\Models\Quote;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function deficiencyAuthorizationUser(array $permissions = ['deficiencies.view', 'deficiencies.authorize']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('authorization route redirects guests to login', function () {
    $deficiency = Deficiency::factory()->create();

    $this->post(route('deficiencies.authorizations.store', $deficiency->id), [
        'autorizado_por' => 'Juan Pérez',
        'canal' => 'whatsapp',
        'fecha' => now()->toDateString(),
    ])->assertRedirect(route('login'));
});

test('user without deficiencies.authorize permission is forbidden', function () {
    $user = User::factory()->create();
    $deficiency = Deficiency::factory()->create(['estado' => 'esperando_autorizacion']);

    $this->actingAs($user)
        ->post(route('deficiencies.authorizations.store', $deficiency->id), [
            'autorizado_por' => 'Juan Pérez',
            'canal' => 'whatsapp',
            'fecha' => now()->toDateString(),
        ])
        ->assertForbidden();

    expect($deficiency->fresh()->estado)->toBe('esperando_autorizacion');
    $this->assertDatabaseCount('deficiency_authorizations', 0);
});

test('authorizing an additional creates the record and transitions the deficiency to autorizada', function () {
    $user = deficiencyAuthorizationUser();
    $deficiency = Deficiency::factory()->create(['estado' => 'esperando_autorizacion']);
    $quote = Quote::factory()->create();

    $this->actingAs($user)
        ->post(route('deficiencies.authorizations.store', $deficiency->id), [
            'quote_id' => $quote->id,
            'autorizado_por' => 'Juan Pérez',
            'canal' => 'whatsapp',
            'fecha' => now()->toDateString(),
            'observacion' => 'Cliente confirmó por WhatsApp',
        ])
        ->assertRedirect();

    expect($deficiency->fresh()->estado)->toBe('autorizada');

    $this->assertDatabaseHas('deficiency_authorizations', [
        'deficiency_id' => $deficiency->id,
        'quote_id' => $quote->id,
        'autorizado_por' => 'Juan Pérez',
        'canal' => 'whatsapp',
        'observacion' => 'Cliente confirmó por WhatsApp',
    ]);
});

test('authorization record is optional to link to a quote', function () {
    $user = deficiencyAuthorizationUser();
    $deficiency = Deficiency::factory()->create(['estado' => 'esperando_autorizacion']);

    $this->actingAs($user)
        ->post(route('deficiencies.authorizations.store', $deficiency->id), [
            'autorizado_por' => 'María López',
            'canal' => 'presencial',
            'fecha' => now()->toDateString(),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('deficiency_authorizations', [
        'deficiency_id' => $deficiency->id,
        'quote_id' => null,
        'autorizado_por' => 'María López',
        'canal' => 'presencial',
    ]);
});

test('authorization cannot be registered outside esperando_autorizacion state', function () {
    $user = deficiencyAuthorizationUser();
    $deficiency = Deficiency::factory()->create(['estado' => 'detectada']);

    $this->actingAs($user)
        ->post(route('deficiencies.authorizations.store', $deficiency->id), [
            'autorizado_por' => 'Juan Pérez',
            'canal' => 'whatsapp',
            'fecha' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('estado');

    expect($deficiency->fresh()->estado)->toBe('detectada');
    $this->assertDatabaseCount('deficiency_authorizations', 0);
});

test('rejecting a deficiency does not create an authorization record', function () {
    $user = deficiencyAuthorizationUser(['deficiencies.view', 'deficiencies.authorize', 'deficiencies.create']);
    $deficiency = Deficiency::factory()->create(['estado' => 'esperando_autorizacion']);

    $this->actingAs($user)
        ->patch(route('deficiencies.status', $deficiency->id), ['estado' => 'rechazada'])
        ->assertRedirect();

    expect($deficiency->fresh()->estado)->toBe('rechazada');
    $this->assertDatabaseCount('deficiency_authorizations', 0);
});

test('authorization validation requires autorizado_por, canal and fecha', function () {
    $user = deficiencyAuthorizationUser();
    $deficiency = Deficiency::factory()->create(['estado' => 'esperando_autorizacion']);

    $this->actingAs($user)
        ->post(route('deficiencies.authorizations.store', $deficiency->id), [])
        ->assertSessionHasErrors(['autorizado_por', 'canal', 'fecha']);

    $this->assertDatabaseCount('deficiency_authorizations', 0);
});
