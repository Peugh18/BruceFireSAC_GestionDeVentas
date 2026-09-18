<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function ensureRole(string $name, array $permissions = []): Role
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role = Role::findOrCreate($name, 'web');
    $role->syncPermissions($permissions);

    return $role;
}

function adminUser(): User
{
    ensureRole('Administrador', ['users.manage', 'roles.manage']);

    $user = User::factory()->create(['activo' => true]);
    $user->assignRole('Administrador');

    return $user;
}

test('administration routes redirect guests to login', function () {
    $this->get(route('administration.users.index'))->assertRedirect(route('login'));
    $this->get(route('administration.roles.index'))->assertRedirect(route('login'));
});

test('user without users.manage or roles.manage is forbidden', function () {
    ensureRole('Vendedor', ['clients.view']);
    $user = User::factory()->create();
    $user->assignRole('Vendedor');

    $this->actingAs($user)->get(route('administration.users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('administration.roles.index'))->assertForbidden();
});

test('only administrador role can access administration routes', function () {
    ensureRole('Administrador', ['users.manage', 'roles.manage']);
    ensureRole('Gerente', ['clients.view']);
    ensureRole('Vendedor', ['clients.view']);

    $gerente = User::factory()->create();
    $gerente->assignRole('Gerente');

    $vendedor = User::factory()->create();
    $vendedor->assignRole('Vendedor');

    $admin = adminUser();

    $this->actingAs($gerente)->get(route('administration.users.index'))->assertForbidden();
    $this->actingAs($vendedor)->get(route('administration.users.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('administration.users.index'))->assertOk();
});

test('admin can create a user with a defined password and assign a role', function () {
    $admin = adminUser();
    ensureRole('Vendedor', ['clients.view']);

    $this->actingAs($admin)->post(route('administration.users.store'), [
        'name' => 'Juan Perez',
        'email' => 'juan@example.com',
        'password' => 'clave-segura-123',
        'role' => 'Vendedor',
        'activo' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'juan@example.com', 'activo' => true]);

    $created = User::where('email', 'juan@example.com')->firstOrFail();
    expect($created->hasRole('Vendedor'))->toBeTrue();
    expect(Hash::check('clave-segura-123', $created->password))->toBeTrue();
});

test('admin can create a user with an auto-generated password', function () {
    $admin = adminUser();
    ensureRole('Almacén', ['inventory.view']);

    $response = $this->actingAs($admin)->post(route('administration.users.store'), [
        'name' => 'Maria Lopez',
        'email' => 'maria@example.com',
        'role' => 'Almacén',
        'activo' => true,
    ]);

    $response->assertRedirect();
    expect(session('status'))->toContain('Contrasena generada');

    $created = User::where('email', 'maria@example.com')->firstOrFail();
    expect($created->hasRole('Almacén'))->toBeTrue();
});

test('admin can edit a user role and active status', function () {
    $admin = adminUser();
    ensureRole('Vendedor', ['clients.view']);
    ensureRole('Almacén', ['inventory.view']);

    $user = User::factory()->create(['activo' => true]);
    $user->assignRole('Vendedor');

    $this->actingAs($admin)->put(route('administration.users.update', $user->id), [
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'Almacén',
        'activo' => false,
    ])->assertRedirect();

    $user->refresh();
    expect($user->activo)->toBeFalse();
    expect($user->hasRole('Almacén'))->toBeTrue();
    expect($user->hasRole('Vendedor'))->toBeFalse();
});

test('cannot deactivate the only active administrador', function () {
    $admin = adminUser();

    $this->actingAs($admin)->put(route('administration.users.update', $admin->id), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => 'Administrador',
        'activo' => false,
    ])->assertSessionHasErrors('activo');

    expect($admin->fresh()->activo)->toBeTrue();
});

test('cannot reassign the role of the only active administrador', function () {
    $admin = adminUser();
    ensureRole('Vendedor', ['clients.view']);

    $this->actingAs($admin)->put(route('administration.users.update', $admin->id), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => 'Vendedor',
        'activo' => true,
    ])->assertSessionHasErrors('role');

    expect($admin->fresh()->hasRole('Administrador'))->toBeTrue();
});

test('can deactivate an administrador when another active administrador remains', function () {
    $adminOne = adminUser();
    $adminTwo = User::factory()->create(['activo' => true]);
    $adminTwo->assignRole('Administrador');

    $this->actingAs($adminOne)->put(route('administration.users.update', $adminTwo->id), [
        'name' => $adminTwo->name,
        'email' => $adminTwo->email,
        'role' => 'Administrador',
        'activo' => false,
    ])->assertRedirect();

    expect($adminTwo->fresh()->activo)->toBeFalse();
});

test('editing a role permissions is reflected in user abilities', function () {
    $admin = adminUser();
    $role = ensureRole('Vendedor', ['clients.view']);

    $vendedor = User::factory()->create();
    $vendedor->assignRole('Vendedor');

    expect($vendedor->can('quotes.create'))->toBeFalse();

    Permission::findOrCreate('quotes.create', 'web');

    $this->actingAs($admin)->put(route('administration.roles.update', $role->id), [
        'permissions' => ['clients.view', 'quotes.create'],
    ])->assertRedirect();

    expect($vendedor->fresh()->can('quotes.create'))->toBeTrue();
});

test('cannot strip users.manage or roles.manage from administrador role', function () {
    $admin = adminUser();
    $role = Role::where('name', 'Administrador')->firstOrFail();

    $this->actingAs($admin)->put(route('administration.roles.update', $role->id), [
        'permissions' => ['roles.manage'],
    ])->assertSessionHasErrors('permissions');

    $this->actingAs($admin)->put(route('administration.roles.update', $role->id), [
        'permissions' => ['users.manage'],
    ])->assertSessionHasErrors('permissions');

    expect($role->fresh()->hasPermissionTo('users.manage'))->toBeTrue();
    expect($role->fresh()->hasPermissionTo('roles.manage'))->toBeTrue();
});

test('roles index reports permission and user counts', function () {
    $admin = adminUser();
    ensureRole('Vendedor', ['clients.view', 'quotes.view']);

    $vendedor = User::factory()->create();
    $vendedor->assignRole('Vendedor');

    $response = $this->actingAs($admin)->get(route('administration.roles.index'));

    $response->assertOk();

    $roles = collect($response->viewData('page')['props']['roles']);
    $vendedorRow = $roles->firstWhere('name', 'Vendedor');

    expect($vendedorRow['permissions_count'])->toBe(2);
    expect($vendedorRow['users_count'])->toBe(1);
});

test('role show groups permissions by module prefix', function () {
    $admin = adminUser();
    $role = ensureRole('Vendedor', ['clients.view', 'quotes.view']);
    Permission::findOrCreate('sales.view', 'web');

    $response = $this->actingAs($admin)->get(route('administration.roles.show', $role->id));

    $response->assertOk();

    $props = $response->viewData('page')['props'];

    expect($props['permissionGroups'])->toHaveKeys(['clients', 'quotes', 'sales']);
    expect($props['rolePermissions'])->toEqualCanonicalizing(['clients.view', 'quotes.view']);
});
