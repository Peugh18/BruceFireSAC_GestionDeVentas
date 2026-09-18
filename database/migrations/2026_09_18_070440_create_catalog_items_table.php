<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->enum('tipo', ['producto', 'servicio', 'repuesto']);
            $table->string('codigo', 30)->unique();
            $table->string('categoria', 100);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('unidad', 30);
            $table->decimal('precio', 12, 2);
            $table->boolean('aplica_igv')->default(true);
            $table->boolean('activo')->default(true);
            $table->boolean('controla_stock')->default(false);
            $table->boolean('control_serializado')->default(false);
            $table->boolean('genera_barcode')->default(false);
            $table->string('tipo_tecnico', 100)->nullable();
            $table->boolean('requiere_orden')->default(false);
            $table->boolean('requiere_certificado')->default(false);
            $table->string('checklist_aplicable')->nullable();
            $table->timestamps();
            $table->index(['tipo', 'nombre']);
        });
    }
};
