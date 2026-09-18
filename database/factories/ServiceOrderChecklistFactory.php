<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrderChecklist>
 */
class ServiceOrderChecklistFactory extends Factory
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
            'equipment_id' => Equipment::factory(),
            'completado_por_user_id' => User::factory(),
            'completado_en' => now(),
            'estado' => 'completado',
        ];
    }
}
