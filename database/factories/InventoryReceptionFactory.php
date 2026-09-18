<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Models\InventoryReception;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryReception> */
class InventoryReceptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'catalog_item_id' => CatalogItem::factory(),
            'proveedor' => fake()->company(),
            'documento_referencia' => fake()->bothify('GR-####'),
            'fecha' => now()->toDateString(),
            'cantidad' => 5,
            'cantidad_conforme' => 5,
            'cantidad_observada' => 0,
            'usuario_id' => User::factory(),
        ];
    }
}
