<?php

namespace Database\Factories;

use App\Models\ChecklistItem;
use App\Models\ServiceOrderChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checklist_id' => ServiceOrderChecklist::factory(),
            'componente' => $this->faker->randomElement([
                'identificacion', 'cilindro', 'corrosion', 'golpes_deformacion',
                'valvula', 'manometro', 'pasador', 'precinto', 'manguera',
                'boquilla_difusor', 'manija_palanca', 'rotulado', 'agente_carga', 'servicio',
            ]),
            'condicion' => 'conforme',
            'foto_path' => null,
            'nota' => null,
            'accion_recomendada' => null,
        ];
    }
}
