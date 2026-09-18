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
        Schema::create('credit_debit_notes', function (Blueprint $table): void {
            $table->id();

            // Notes never exist without an already issued (and accepted)
            // comprobante: never created as an independent sale.
            $table->foreignId('cpe_afectado_id')->constrained('electronic_documents')->restrictOnDelete();

            $table->enum('tipo', ['nota_credito', 'nota_debito']);
            $table->string('motivo', 2);
            $table->text('detalle');
            $table->decimal('importe', 10, 2);
            $table->date('fecha');

            // Same SUNAT submission pattern as electronic_documents.
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
        Schema::dropIfExists('credit_debit_notes');
    }
};
