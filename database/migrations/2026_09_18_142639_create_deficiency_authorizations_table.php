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
        Schema::create('deficiency_authorizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deficiency_id')->constrained('deficiencies')->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->string('autorizado_por');
            $table->enum('canal', ['whatsapp', 'presencial']);
            $table->date('fecha');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index('deficiency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deficiency_authorizations');
    }
};
