<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CatalogPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['catalog.view', 'catalog.create', 'catalog.update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (['Gerente', 'Administrador', 'Vendedor', 'Almacén'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            $role->givePermissionTo('catalog.view');

            if (in_array($roleName, ['Gerente', 'Administrador'], true)) {
                $role->givePermissionTo(['catalog.create', 'catalog.update']);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
