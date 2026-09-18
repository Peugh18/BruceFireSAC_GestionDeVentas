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
        Schema::create('deficiencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->restrictOnDelete();
            $table->foreignId('checklist_item_id')->nullable()->constrained('checklist_items')->nullOnDelete();
            $table->string('componente');
            $table->string('condicion')->default('observado');
            $table->string('foto_path')->nullable();
            $table->text('nota')->nullable();
            $table->text('accion_recomendada')->nullable();
            $table->text('repuesto_sugerido')->nullable();
            $table->foreignId('catalog_item_id')->nullable()->constrained('catalog_items')->nullOnDelete();
            $table->boolean('requiere_autorizacion')->default(false);
            $table->enum('estado', [
                'detectada',
                'esperando_autorizacion',
                'autorizada',
                'rechazada',
                'en_correccion',
                'resuelta',
            ])->default('detectada');
            $table->text('resolucion')->nullable();
            $table->foreignId('resuelto_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resuelto_en')->nullable();
            $table->timestamps();

            $table->index(['service_order_id', 'estado']);
            $table->index(['equipment_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deficiencies');
    }
};
