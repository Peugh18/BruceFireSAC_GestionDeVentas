<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentEvent>
 */
class EquipmentEventFactory extends Factory
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
            'tipo' => $faker->randomElement(['alta', 'transferencia', 'cambio_estado']),
            'descripcion' => $faker->sentence(),
            'fecha' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'user_id' => null,
        ];
    }
}
