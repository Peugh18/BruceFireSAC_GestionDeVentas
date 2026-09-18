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
        Schema::create('equipment_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('origen_client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('origen_client_site_id')->nullable()->constrained('client_sites')->nullOnDelete();
            $table->foreignId('destino_client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('destino_client_site_id')->nullable()->constrained('client_sites')->nullOnDelete();
            $table->date('fecha');
            $table->string('motivo');
            $table->foreignId('responsable_user_id')->constrained('users')->restrictOnDelete();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_transfers');
    }
};
