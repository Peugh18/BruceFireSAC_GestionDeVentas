<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceOrder> */
class ServiceOrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'tipo_servicio' => fake()->randomElement(['recarga', 'mantenimiento', 'prueba_hidrostatica', 'inspeccion']),
            'fecha' => fake()->dateTimeBetween('-1 week', '+1 week')->format('Y-m-d'),
            'prioridad' => fake()->randomElement(['baja', 'media', 'alta']),
            'observaciones' => 'Servicio programado para los equipos de protección contra incendios del cliente.',
        ];
    }

    public function withEquipment(int $count = 2): static
    {
        return $this->afterCreating(function (ServiceOrder $order) use ($count): void {
            $equipment = Equipment::factory()->count($count)->for($order->client)->create();
            $order->equipment()->attach($equipment->modelKeys());
        });
    }
}
