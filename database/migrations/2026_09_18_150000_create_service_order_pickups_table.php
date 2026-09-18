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
        Schema::create('service_order_pickups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('client_site_id')->nullable()->constrained('client_sites')->nullOnDelete();
            $table->string('contacto');
            $table->dateTime('fecha_hora_recojo');
            $table->integer('cantidad')->default(1);
            $table->text('observaciones')->nullable();
            $table->string('conforme_nombre')->nullable();
            $table->string('conforme_dni')->nullable();
            $table->text('conforme_firma')->nullable();

            // Cadena de Custodia Timestamps and Users
            $table->foreignId('recogido_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recogido_en')->nullable();

            $table->foreignId('recibido_planta_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recibido_planta_en')->nullable();

            $table->foreignId('entregado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entregado_en')->nullable();

            $table->foreignId('recibido_cliente_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recibido_cliente_en')->nullable();
            $table->string('recibido_cliente_nombre')->nullable();

            $table->timestamps();

            $table->index(['service_order_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_order_pickups');
    }
};
