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
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendedor_user_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->enum('condicion_pago', ['contado', 'credito'])->default('contado');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('total', 12, 2);
            $table->enum('estado', ['pendiente', 'completada', 'anulada'])->default('completada');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('service_order_id')->nullable()->index();
            $table->timestamps();

            $table->index(['client_id', 'fecha']);
            $table->index(['vendedor_user_id', 'id']);
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->restrictOnDelete();
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('sale_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->enum('forma_pago', [
                'efectivo',
                'transferencia',
                'yape',
                'plin',
                'pos',
                'deposito',
                'otro',
            ]);
            $table->decimal('monto', 12, 2);
            $table->string('referencia')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->integer('numero_cuota');
            $table->decimal('monto', 12, 2);
            $table->decimal('monto_pendiente', 12, 2);
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['pendiente', 'pagado_parcial', 'pagado', 'vencido'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_installments');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
