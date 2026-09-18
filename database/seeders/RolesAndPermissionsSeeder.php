<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permission list per the master document, §36 (Matriz de permisos).
     *
     * @var list<string>
     */
    private const PERMISSIONS = [
        'clients.view', 'clients.create', 'clients.update',
        'quotes.view', 'quotes.create', 'quotes.update', 'quotes.convert',
        'sales.view', 'sales.create', 'sales.scan_units',
        'inventory.view', 'inventory.receive', 'inventory.adjust',
        'service_orders.view', 'service_orders.create', 'service_orders.assign',
        'service_orders.receive', 'service_orders.execute', 'service_orders.close',
        'deficiencies.create', 'deficiencies.authorize', 'deficiencies.resolve',
        'certificates.view', 'certificates.generate', 'certificates.void',
        'billing.view', 'billing.issue', 'billing.retry', 'billing.credit_note',
        'collections.view', 'collections.register_payment',
        'reports.view',
        'users.manage', 'roles.manage', 'settings.manage', 'audit.view',
    ];

    /**
     * Role -> permission mapping per the master document, §35 (Roles y permisos).
     *
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'Gerente' => [
            'clients.view',
            'quotes.view',
            'sales.view',
            'inventory.view',
            'service_orders.view',
            'certificates.view',
            'billing.view',
            'collections.view',
            'reports.view',
            'audit.view',
        ],
        'Vendedor' => [
            'clients.view', 'clients.create', 'clients.update',
            'quotes.view', 'quotes.create', 'quotes.update', 'quotes.convert',
            'sales.view', 'sales.create', 'sales.scan_units',
            'service_orders.view', 'service_orders.create',
            'deficiencies.create',
            'certificates.view',
            'billing.view',
            'collections.view',
        ],
        'Almacén' => [
            'inventory.view', 'inventory.receive', 'inventory.adjust',
        ],
        'Técnico de Planta' => [
            'service_orders.view', 'service_orders.receive', 'service_orders.execute', 'service_orders.close',
            'deficiencies.create', 'deficiencies.resolve',
            'certificates.generate',
        ],
        'Técnico de Campo' => [
            'service_orders.view', 'service_orders.execute', 'service_orders.close',
            'deficiencies.create', 'deficiencies.resolve',
        ],
        'Administrador' => [
            'users.manage', 'roles.manage', 'settings.manage', 'audit.view',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
