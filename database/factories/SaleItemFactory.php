<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 10);
        $precioUnitario = $this->faker->randomFloat(2, 20, 500);
        $descuento = 0;
        $subtotal = round(($cantidad * $precioUnitario) - $descuento, 2);

        return [
            'sale_id' => Sale::factory(),
            'catalog_item_id' => CatalogItem::factory(),
            'inventory_unit_id' => null,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'descuento' => $descuento,
            'subtotal' => $subtotal,
        ];
    }
}
