<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\EquipmentTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentTransfer>
 */
class EquipmentTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake('es_PE');

        return [
            'equipment_id' => Equipment::factory(),
            'origen_client_id' => Client::factory(),
            'origen_client_site_id' => null,
            'destino_client_id' => Client::factory(),
            'destino_client_site_id' => null,
            'fecha' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'motivo' => $faker->randomElement(['Venta del equipo a otro cliente', 'Cambio de sede', 'Cesión temporal']),
            'responsable_user_id' => User::factory(),
            'observacion' => $faker->optional()->sentence(),
        ];
    }
}
