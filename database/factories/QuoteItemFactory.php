<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteItem>
 */
class QuoteItemFactory extends Factory
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
            'quote_id' => Quote::factory(),
            'catalog_item_id' => CatalogItem::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'descuento' => $descuento,
            'subtotal' => $subtotal,
        ];
    }
}
