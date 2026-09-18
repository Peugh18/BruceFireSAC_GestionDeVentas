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
        Schema::create('electronic_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->unique()->constrained('sales')->restrictOnDelete();
            $table->enum('tipo', ['factura', 'boleta']);
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

            $table->unique(['tipo', 'serie', 'correlativo']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electronic_documents');
    }
};
