<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'tipo' => 'operatividad_garantia',
            'estado' => 'vigente',
            'fecha_emision' => now()->toDateString(),
            'fecha_vigencia' => now()->addYear()->toDateString(),
            'observaciones' => null,
            'generado_por_user_id' => User::factory(),
        ];
    }
}
