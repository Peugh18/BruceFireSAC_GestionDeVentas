<?php

namespace Database\Factories;

use App\Models\ElectronicDocument;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ElectronicDocument>
 */
class ElectronicDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'tipo' => 'factura',
            'serie' => 'F001',
            'correlativo' => ElectronicDocument::nextCorrelativo('factura', 'F001'),
            'xml_path' => null,
            'cdr_path' => null,
            'hash' => null,
            'estado' => 'pendiente',
            'respuesta_sunat' => null,
            'error' => null,
            'intentos' => 0,
            'fecha_envio' => null,
        ];
    }

    public function boleta(): static
    {
        return $this->state(fn (): array => [
            'tipo' => 'boleta',
            'serie' => 'B001',
            'correlativo' => ElectronicDocument::nextCorrelativo('boleta', 'B001'),
        ]);
    }

    public function aceptado(): static
    {
        return $this->state(fn (): array => [
            'estado' => 'aceptado',
            'respuesta_sunat' => '0 - La Factura numero ha sido aceptada',
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
