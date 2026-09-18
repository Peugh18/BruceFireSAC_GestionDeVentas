<?php

namespace Database\Factories;

use App\Models\ShippingGuide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingGuide>
 */
class ShippingGuideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => null,
            'motivo_traslado' => 'traslado_entre_establecimientos',
            'fecha_inicio' => now()->addDay()->toDateString(),
            'origen' => 'Av. Principal 123, Lima',
            'destino' => 'Jr. Los Alamos 456, Lima',
            'destinatario_client_id' => null,
            'destinatario_nombre' => $this->faker->name(),
            'destinatario_documento' => $this->faker->numerify('########'),
            'peso_total' => $this->faker->randomFloat(2, 5, 200),
            'modalidad' => 'transporte_privado',
            'transportista_razon_social' => null,
            'transportista_ruc' => null,
            'vehiculo_placa' => 'ABC-123',
            'conductor_nombre' => $this->faker->name(),
            'conductor_licencia' => $this->faker->numerify('Q#########'),
            'observaciones' => null,
            'serie' => 'T001',
            'correlativo' => ShippingGuide::nextCorrelativo('T001'),
            'estado' => 'pendiente',
            'intentos' => 0,
        ];
    }

    public function transportePublico(): static
    {
        return $this->state(fn (): array => [
            'modalidad' => 'transporte_publico',
            'transportista_razon_social' => 'Transportes Rapidos S.A.C.',
            'transportista_ruc' => $this->faker->numerify('20#########'),
            'vehiculo_placa' => null,
            'conductor_nombre' => null,
            'conductor_licencia' => null,
        ]);
    }

    public function aceptado(): static
    {
        return $this->state(fn (): array => [
            'estado' => 'aceptado',
            'respuesta_sunat' => '0 - La Guia de Remision ha sido aceptada',
            'intentos' => 1,
            'fecha_envio' => now(),
        ]);
    }

    public function error(): static
    {
        return $this->state(fn (): array => [
            'estado' => 'error',
            'error' => 'No se pudo conectar con el servicio de SUNAT.',
            'intentos' => 1,
            'fecha_envio' => now(),
        ]);
    }
}
