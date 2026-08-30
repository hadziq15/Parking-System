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
        Schema::create('area_parkirs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->string('lokasi');
            $table->integer('kapasitas');
            $table->foreignUuid('tarif_id')->constrained('tarifs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_parkirs');
    }
};
