<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake('es_PE');
        $tipoDocumento = $faker->randomElement(['dni', 'ruc']);

        return [
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $tipoDocumento === 'ruc' ? $faker->numerify('20#########') : $faker->numerify('########'),
            'razon_social' => $tipoDocumento === 'ruc' ? $faker->company() : $faker->name(),
            'nombre_comercial' => $tipoDocumento === 'ruc' ? $faker->companySuffix() : null,
            'telefono' => $faker->numerify('01#######'),
            'whatsapp' => $faker->numerify('9########'),
            'email' => $faker->unique()->safeEmail(),
            'direccion_fiscal' => $faker->streetAddress(),
            'departamento' => $faker->randomElement(['Lima', 'Arequipa', 'La Libertad', 'Piura', 'Cusco']),
            'provincia' => $faker->city(),
            'distrito' => $faker->citySuffix(),
            'ubigeo' => $faker->numerify('######'),
            'activo' => true,
            'observaciones' => $faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the client is inactive.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
