<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_payments', function (Blueprint $table): void {
            $table->foreignId('sale_installment_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('fecha')->nullable()->index();
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('idempotency_key')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sale_installment_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropIndex(['fecha']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['fecha', 'observaciones', 'idempotency_key']);
        });
    }
};
