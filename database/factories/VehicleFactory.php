<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
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
            'client_id' => Client::factory(),
            'placa' => strtoupper($faker->bothify('???-###')),
            'marca' => $faker->randomElement(['Toyota', 'Hyundai', 'Kia', 'Nissan', 'Volvo', 'Mercedes-Benz']),
            'modelo' => $faker->word(),
            'descripcion' => $faker->optional()->sentence(),
            'activo' => true,
        ];
    }
}
