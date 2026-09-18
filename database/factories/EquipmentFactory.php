<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
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
            'client_site_id' => null,
            'vehicle_id' => null,
            'origen' => $faker->randomElement(['vendido_bruce_fire', 'externo', 'desconocido']),
            'tipo_equipo' => $faker->randomElement(['Extintor PQS', 'Extintor CO2', 'Extintor agua pulverizada', 'Gabinete contra incendio']),
            'agente' => $faker->randomElement(['PQS ABC', 'CO2', 'Agua pulverizada', 'Espuma AFFF']),
            'capacidad' => $faker->randomElement(['4 kg', '6 kg', '9 kg', '12 kg', '25 lb']),
            'marca' => $faker->randomElement(['Amerex', 'Ansul', 'Badger', 'Pyro-Chem', 'Peru Fire']),
            'serie_fabricante' => $faker->bothify('SN-#####'),
            'anio_fabricacion' => (string) $faker->numberBetween(2015, 2025),
            'ubicacion' => $faker->randomElement(['Recepción', 'Pasillo principal', 'Almacén', 'Cocina', 'Sala de máquinas']),
            'estado' => 'activo',
            'ultima_atencion' => null,
            'proxima_atencion' => null,
            'ultima_ph' => null,
            'proxima_ph' => null,
            'observaciones' => $faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the equipment's data could not be fully read on site.
     */
    public function noLegible(): static
    {
        return $this->state(fn (array $attributes) => [
            'marca' => null,
            'serie_fabricante' => null,
            'anio_fabricacion' => null,
        ]);
    }

    /**
     * Indicate that the equipment is out of service.
     */
    public function fueraDeServicio(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'fuera_de_servicio',
        ]);
    }
}
