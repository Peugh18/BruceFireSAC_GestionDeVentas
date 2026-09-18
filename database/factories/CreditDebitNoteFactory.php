<?php

namespace Database\Factories;

use App\Models\CreditDebitNote;
use App\Models\ElectronicDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditDebitNote>
 */
class CreditDebitNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cpe_afectado_id' => ElectronicDocument::factory()->aceptado(),
            'tipo' => 'nota_credito',
            'motivo' => '06',
            'detalle' => 'Devolución total del producto por defecto de fábrica.',
            'importe' => 100,
            'fecha' => now()->toDateString(),
            'serie' => 'FC01',
            'correlativo' => CreditDebitNote::nextCorrelativo('nota_credito', 'FC01'),
            'estado' => 'pendiente',
            'intentos' => 0,
        ];
    }

    public function notaDebito(): static
    {
        return $this->state(fn (): array => [
            'tipo' => 'nota_debito',
            'motivo' => '01',
            'serie' => 'FD01',
            'correlativo' => CreditDebitNote::nextCorrelativo('nota_debito', 'FD01'),
        ]);
    }

    public function aceptado(): static
    {
        return $this->state(fn (): array => [
            'estado' => 'aceptado',
            'respuesta_sunat' => '0 - La Nota de Credito ha sido aceptada',
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
