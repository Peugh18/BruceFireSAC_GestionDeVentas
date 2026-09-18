<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_item_id')->unique()->constrained('catalog_items')->restrictOnDelete();
            $table->decimal('stock_actual', 12, 3)->default(0);
            $table->decimal('stock_minimo', 12, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_receptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->restrictOnDelete();
            $table->string('proveedor');
            $table->string('documento_referencia');
            $table->date('fecha');
            $table->decimal('cantidad', 12, 3);
            $table->decimal('cantidad_conforme', 12, 3);
            $table->decimal('cantidad_observada', 12, 3);
            $table->text('observacion')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->restrictOnDelete();
            $table->foreignId('inventory_reception_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->enum('tipo', ['entrada', 'salida', 'ajuste']);
            $table->decimal('cantidad', 12, 3);
            $table->decimal('stock_antes', 12, 3);
            $table->decimal('stock_despues', 12, 3);
            $table->string('motivo');
            $table->string('referencia')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->index(['catalog_item_id', 'fecha', 'id']);
            $table->index(['tipo', 'fecha']);
        });

        Schema::create('inventory_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->restrictOnDelete();
            $table->foreignId('inventory_reception_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('salida_movement_id')->nullable()->constrained('inventory_movements')->restrictOnDelete();
            $table->string('serie', 100)->unique();
            $table->string('marca', 100);
            $table->string('capacidad', 100);
            $table->unsignedSmallInteger('anio');
            $table->string('barcode', 100)->nullable()->unique();
            $table->enum('estado', ['disponible', 'vendido', 'reservado'])->default('disponible');
            $table->boolean('conforme')->default(true);
            $table->boolean('en_stock')->default(true);
            $table->timestamps();
            $table->index(['catalog_item_id', 'en_stock', 'estado']);
        });

        DB::table('catalog_items')->where('controla_stock', true)->orderBy('id')->chunkById(500, function (Collection $items): void {
            DB::table('inventory_stocks')->insert($items->map(fn (stdClass $item): array => [
                'catalog_item_id' => $item->id,
                'stock_actual' => 0,
                'stock_minimo' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_units');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_receptions');
        Schema::dropIfExists('inventory_stocks');
    }
};
