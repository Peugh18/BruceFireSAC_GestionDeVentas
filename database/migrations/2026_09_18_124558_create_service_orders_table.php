<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->enum('tipo_servicio', [
                'recarga', 'mantenimiento', 'prueba_hidrostatica', 'inspeccion',
                'instalacion', 'mantenimiento_campo', 'otro',
            ]);
            $table->date('fecha');
            $table->foreignId('tecnico_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');
            $table->text('observaciones')->nullable();
            $table->enum('estado', [
                'pendiente_recepcion', 'recibido_planta', 'en_revision', 'esperando_autorizacion',
                'autorizado', 'en_proceso', 'trabajo_terminado', 'pendiente_datos', 'datos_completos',
                'listo_certificado', 'listo_entrega', 'entregado', 'cerrado',
            ])->default('pendiente_recepcion');
            $table->timestamps();
            $table->index(['estado', 'id']);
            $table->index(['tecnico_user_id', 'id']);
        });

        Schema::create('service_order_equipment', function (Blueprint $table): void {
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->restrictOnDelete();
            $table->primary(['service_order_id', 'equipment_id']);
        });

        Schema::create('service_order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->string('estado_anterior')->nullable();
            $table->string('estado');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_status_histories');
        Schema::dropIfExists('service_order_equipment');
        Schema::dropIfExists('service_orders');
    }
};
