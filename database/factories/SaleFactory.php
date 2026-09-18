<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $igv = round($subtotal * 0.18, 2);
        $total = $subtotal + $igv;

        return [
            'numero' => Sale::nextNumero(),
            'quote_id' => null,
            'client_id' => Client::factory(),
            'client_site_id' => null,
            'vehicle_id' => null,
            'vendedor_user_id' => User::factory(),
            'fecha' => now()->toDateString(),
            'condicion_pago' => $this->faker->randomElement(['contado', 'credito']),
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'estado' => 'completada',
            'observaciones' => $this->faker->optional()->sentence(),
            'service_order_id' => null,
        ];
    }
}
