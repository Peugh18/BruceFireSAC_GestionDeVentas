<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BillingPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * billing.view/issue/retry already exist in RolesAndPermissionsSeeder's
     * permission list, but only billing.view was assigned to roles there.
     * This seeder additively grants billing.issue/retry to the roles that
     * actually operate the billing module.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = ['billing.view', 'billing.issue', 'billing.retry'];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolesPermissions = [
            'Gerente' => ['billing.issue', 'billing.retry'],
            'Vendedor' => ['billing.issue', 'billing.retry'],
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
