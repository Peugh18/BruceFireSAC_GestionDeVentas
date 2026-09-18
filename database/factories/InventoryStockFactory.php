<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Models\InventoryStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryStock> */
class InventoryStockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'catalog_item_id' => fn (): int => CatalogItem::factory()->create(['controla_stock' => false])->id,
            'stock_actual' => 0,
            'stock_minimo' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (InventoryStock $stock): void {
            $stock->catalogItem->update(['controla_stock' => true]);
        });
    }

    public function lowStock(): static
    {
        return $this->state(fn (): array => ['stock_actual' => 2, 'stock_minimo' => 5]);
    }
}
