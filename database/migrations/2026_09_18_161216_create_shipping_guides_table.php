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
        Schema::create('shipping_guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();

            $table->enum('motivo_traslado', [
                'venta',
                'compra',
                'traslado_entre_establecimientos',
                'importacion',
                'exportacion',
                'otros',
            ]);
            $table->date('fecha_inicio');
            $table->string('origen');
            $table->string('destino');

            $table->foreignId('destinatario_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('destinatario_nombre')->nullable();
            $table->string('destinatario_documento')->nullable();

            $table->decimal('peso_total', 10, 2);
            $table->enum('modalidad', ['transporte_publico', 'transporte_privado']);

            // Transporte publico: empresa transportista. Nulo si es privado.
            $table->string('transportista_razon_social')->nullable();
            $table->string('transportista_ruc', 11)->nullable();

            // Transporte privado: vehiculo/conductor propios. Nulo si es publico.
            $table->string('vehiculo_placa')->nullable();
            $table->string('conductor_nombre')->nullable();
            $table->string('conductor_licencia')->nullable();

            $table->text('observaciones')->nullable();

            // Mismo patron de envio SUNAT que electronic_documents.
            $table->string('serie', 4);
            $table->string('correlativo', 8);
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('hash')->nullable();
            $table->enum('estado', ['pendiente', 'aceptado', 'rechazado', 'error'])->default('pendiente');
            $table->text('respuesta_sunat')->nullable();
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->timestamp('fecha_envio')->nullable();

            $table->timestamps();

            $table->unique(['serie', 'correlativo']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_guides');
    }
};
