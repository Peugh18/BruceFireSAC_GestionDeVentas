<?php

namespace Database\Factories;

use App\Models\Deficiency;
use App\Models\Equipment;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deficiency>
 */
class DeficiencyFactory extends Factory
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
            'checklist_item_id' => null,
            'componente' => $this->faker->randomElement(['manguera', 'valvula', 'manometro', 'pasador']),
            'condicion' => 'observado',
            'foto_path' => null,
            'nota' => $this->faker->sentence(),
            'accion_recomendada' => 'Reemplazar componente dañado',
            'repuesto_sugerido' => 'Manguera de extintor PQS 6kg',
            'catalog_item_id' => null,
            'requiere_autorizacion' => true,
            'estado' => 'detectada',
        ];
    }
}
