<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('barcode')->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_site_id')->nullable()->constrained('client_sites')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->enum('origen', ['vendido_bruce_fire', 'externo', 'desconocido'])->default('desconocido');
            $table->string('tipo_equipo');
            $table->string('agente')->nullable();
            $table->string('capacidad')->nullable();
            $table->string('marca')->nullable();
            $table->string('serie_fabricante')->nullable();
            $table->string('anio_fabricacion')->nullable();
            $table->string('ubicacion')->nullable();
            $table->enum('estado', [
                'activo',
                'fuera_de_servicio',
                'reemplazado',
                'retirado',
                'baja_definitiva',
                'no_localizado',
            ])->default('activo');
            $table->date('ultima_atencion')->nullable();
            $table->date('proxima_atencion')->nullable();
            $table->date('ultima_ph')->nullable();
            $table->date('proxima_ph')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
