<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryMovement> */
class InventoryMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'catalog_item_id' => CatalogItem::factory(),
            'tipo' => 'entrada',
            'cantidad' => 5,
            'stock_antes' => 0,
            'stock_despues' => 5,
            'motivo' => 'Recepción de proveedor',
            'referencia' => fake()->bothify('GR-####'),
            'usuario_id' => User::factory(),
            'fecha' => now()->toDateString(),
        ];
    }
}
