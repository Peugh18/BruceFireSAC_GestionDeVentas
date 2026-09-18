<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientSite>
 */
class ClientSiteFactory extends Factory
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
            'tipo' => $faker->randomElement(['oficina', 'tienda', 'planta', 'almacen', 'local', 'sucursal', 'otra']),
            'nombre' => 'Sede '.$faker->city(),
            'direccion' => $faker->streetAddress(),
            'ubigeo' => $faker->numerify('######'),
            'referencia' => $faker->optional()->sentence(),
            'contacto' => $faker->name(),
            'telefono' => $faker->numerify('9########'),
            'email' => $faker->optional()->safeEmail(),
            'activo' => true,
        ];
    }
}
