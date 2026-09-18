<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleInstallment>
 */
class SaleInstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $monto = $this->faker->randomFloat(2, 100, 1000);

        return [
            'sale_id' => Sale::factory(),
            'numero_cuota' => $this->faker->numberBetween(1, 4),
            'monto' => $monto,
            'monto_pendiente' => $monto,
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'estado' => 'pendiente',
        ];
    }
}
