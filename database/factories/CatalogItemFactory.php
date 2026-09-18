<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CatalogItem> */
class CatalogItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tipo' => 'producto',
            'categoria' => 'Seguridad contra incendios',
            'nombre' => fake()->randomElement(['Extintor PQS ABC 6kg', 'Cámara', 'Manguera', 'Válvula', 'Manómetro']),
            'descripcion' => 'Equipo de seguridad y protección contra incendios.',
            'unidad' => 'Unidad',
            'precio' => fake()->randomElement(['120.00', '185.50', '250.00']),
            'aplica_igv' => true,
            'activo' => true,
            'controla_stock' => true,
            'control_serializado' => false,
            'genera_barcode' => false,
            'tipo_tecnico' => null,
            'requiere_orden' => false,
            'requiere_certificado' => false,
            'checklist_aplicable' => null,
        ];
    }

    public function product(): static
    {
        return $this->state(fn (): array => ['tipo' => 'producto']);
    }

    public function service(): static
    {
        return $this->state(fn (): array => [
            'tipo' => 'servicio',
            'categoria' => 'Servicios técnicos',
            'nombre' => fake()->randomElement([
                'Recarga y mantenimiento PQS 6kg', 'Recarga y mantenimiento PQS 9kg',
                'Inspección', 'Instalación', 'Mantenimiento', 'Prueba hidrostática', 'Capacitación',
            ]),
            'descripcion' => 'Servicio técnico de prevención y protección contra incendios.',
            'unidad' => 'Servicio',
            'precio' => fake()->randomElement(['35.00', '60.00', '120.00']),
            'controla_stock' => false,
            'tipo_tecnico' => 'Técnico de Planta',
            'requiere_orden' => true,
            'requiere_certificado' => true,
            'checklist_aplicable' => 'Revisión técnica de extintores',
        ]);
    }

    public function sparePart(): static
    {
        return $this->state(fn (): array => [
            'tipo' => 'repuesto',
            'categoria' => 'Componentes de extintores',
            'nombre' => fake()->randomElement([
                'Manguera', 'Válvula', 'Manómetro', 'Pasador', 'Precinto',
                'Boquilla', 'Difusor', 'Manija', 'Empaques', 'O-ring',
            ]),
            'descripcion' => 'Componente de reemplazo para mantenimiento de extintores.',
            'precio' => fake()->randomElement(['2.50', '8.00', '15.50']),
            'controla_stock' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }
}
