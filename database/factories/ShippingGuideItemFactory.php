<?php

namespace Database\Factories;

use App\Models\ShippingGuide;
use App\Models\ShippingGuideItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingGuideItem>
 */
class ShippingGuideItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipping_guide_id' => ShippingGuide::factory(),
            'sale_item_id' => null,
            'descripcion' => 'Extintor PQS ABC 6kg',
            'cantidad' => $this->faker->numberBetween(1, 10),
            'unidad' => 'NIU',
            'peso' => $this->faker->randomFloat(2, 1, 20),
        ];
    }
}
