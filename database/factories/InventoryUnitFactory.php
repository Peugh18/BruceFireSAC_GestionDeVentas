<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Models\InventoryUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryUnit> */
class InventoryUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'catalog_item_id' => CatalogItem::factory()->state(['control_serializado' => true]),
            'serie' => fake()->unique()->bothify('SER-########'),
            'marca' => 'BRUCE FIRE',
            'capacidad' => '6 kg',
            'anio' => now()->year,
            'barcode' => null,
            'estado' => 'disponible',
            'conforme' => true,
            'en_stock' => true,
        ];
    }

    public function observed(): static
    {
        return $this->state(fn (): array => ['conforme' => false, 'en_stock' => false, 'estado' => 'reservado']);
    }
}
