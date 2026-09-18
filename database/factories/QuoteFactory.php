<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
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
            'numero' => Quote::nextNumero(),
            'client_id' => Client::factory(),
            'client_site_id' => null,
            'vehicle_id' => null,
            'vendedor_user_id' => User::factory(),
            'fecha' => now()->toDateString(),
            'vigencia' => now()->addDays(15)->toDateString(),
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'condicion_propuesta' => $this->faker->randomElement(['contado', 'credito']),
            'observaciones' => $this->faker->optional()->sentence(),
            'estado' => 'borrador',
        ];
    }
}
