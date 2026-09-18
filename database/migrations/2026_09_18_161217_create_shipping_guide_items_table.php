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
        Schema::create('shipping_guide_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_guide_id')->constrained('shipping_guides')->cascadeOnDelete();

            // Cuando el bien proviene de una venta existente; nulo para bienes de lista libre.
            $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();

            $table->string('descripcion');
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad')->default('NIU');
            $table->decimal('peso', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_guide_items');
    }
};
