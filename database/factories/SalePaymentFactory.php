<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalePayment>
 */
class SalePaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'forma_pago' => $this->faker->randomElement(['efectivo', 'transferencia', 'yape', 'plin', 'pos', 'deposito', 'otro']),
            'monto' => $this->faker->randomFloat(2, 50, 2000),
            'referencia' => $this->faker->optional()->numerify('REF-#######'),
        ];
    }
}
