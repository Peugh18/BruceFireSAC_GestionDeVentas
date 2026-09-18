<?php

namespace Database\Factories;

use App\Models\Deficiency;
use App\Models\DeficiencyAuthorization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeficiencyAuthorization>
 */
class DeficiencyAuthorizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deficiency_id' => Deficiency::factory(),
            'quote_id' => null,
            'autorizado_por' => $this->faker->name(),
            'canal' => $this->faker->randomElement(['whatsapp', 'presencial']),
            'fecha' => now()->toDateString(),
            'observacion' => null,
        ];
    }
}
