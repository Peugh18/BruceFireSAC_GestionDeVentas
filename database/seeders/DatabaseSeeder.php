<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(CatalogPermissionsSeeder::class);
        $this->call(EquipmentPermissionsSeeder::class);
        $this->call(ChecklistPermissionsSeeder::class);
        $this->call(PickupPermissionsSeeder::class);
        $this->call(CertificatePermissionsSeeder::class);
        $this->call(BillingPermissionsSeeder::class);
        $this->call(ShippingPermissionsSeeder::class);
        $this->call(GerenteFullAccessSeeder::class);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->assignRole('Gerente');
    }
}
