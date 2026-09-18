<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GerenteFullAccessSeeder extends Seeder
{
    /**
     * Gerente es el rol unico de administracion general del sistema: debe
     * tener TODOS los permisos que existan, sin importar en que orden se
     * sembraron los modulos. Corre al final de DatabaseSeeder.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'Gerente', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());
    }
}
