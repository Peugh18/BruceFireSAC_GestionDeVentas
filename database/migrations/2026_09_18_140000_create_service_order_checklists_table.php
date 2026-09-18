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
        Schema::create('service_order_checklists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->restrictOnDelete();
            $table->foreignId('completado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completado_en')->nullable();
            $table->enum('estado', ['borrador', 'completado'])->default('borrador');
            $table->timestamps();

            $table->unique(['service_order_id', 'equipment_id']);
        });

        Schema::create('checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checklist_id')->constrained('service_order_checklists')->cascadeOnDelete();
            $table->string('componente');
            $table->enum('condicion', ['conforme', 'observado', 'no_aplica'])->default('conforme');
            $table->string('foto_path')->nullable();
            $table->text('nota')->nullable();
            $table->text('accion_recomendada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('service_order_checklists');
    }
};
