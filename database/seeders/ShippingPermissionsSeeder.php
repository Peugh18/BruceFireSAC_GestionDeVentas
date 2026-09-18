<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShippingPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'shipping_guides.view',
            'shipping_guides.create',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolesPermissions = [
            'Gerente' => ['shipping_guides.view'],
            'Vendedor' => ['shipping_guides.view', 'shipping_guides.create'],
            'Técnico de Campo' => ['shipping_guides.view', 'shipping_guides.create'],
        ];

        foreach ($rolesPermissions as $roleName => $rolePerms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($rolePerms);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
