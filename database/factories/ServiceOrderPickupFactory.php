<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientSite;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderPickup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrderPickup>
 */
class ServiceOrderPickupFactory extends Factory
{
    protected $model = ServiceOrderPickup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'client_id' => Client::factory(),
            'client_site_id' => ClientSite::factory(),
            'contacto' => fake()->name(),
            'fecha_hora_recojo' => now(),
            'cantidad' => fake()->numberBetween(1, 10),
            'observaciones' => fake()->sentence(),
            'conforme_nombre' => fake()->name(),
            'conforme_dni' => fake()->numerify('########'),
            'conforme_firma' => null,
            'recogido_por_user_id' => User::factory(),
            'recogido_en' => now(),
            'recibido_planta_user_id' => null,
            'recibido_planta_en' => null,
            'entregado_por_user_id' => null,
            'entregado_en' => null,
            'recibido_cliente_por_user_id' => null,
            'recibido_cliente_en' => null,
            'recibido_cliente_nombre' => null,
        ];
    }
}
