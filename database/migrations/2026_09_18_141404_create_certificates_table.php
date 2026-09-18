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
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->restrictOnDelete();
            $table->enum('tipo', [
                'operatividad_garantia',
                'prueba_hidrostatica',
                'capacitacion',
                'deteccion_alarma',
                'otro',
            ]);
            $table->string('numero')->unique();
            $table->string('token', 64)->unique();
            $table->enum('estado', ['vigente', 'vencido', 'reemplazado', 'anulado'])->default('vigente');
            $table->date('fecha_emision');
            $table->date('fecha_vigencia')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('generado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['service_order_id', 'tipo']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
