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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kendaraan_id')->constrained('kendaraans')->onDelete('cascade');
            $table->foreignUuid('tarif_id')->constrained('tarifs')->onDelete('cascade');
            $table->foreignUuid('area_parkir_id')->constrained('area_parkirs')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->datetime('waktu_masuk');
            $table->datetime('waktu_keluar')->nullable();
            $table->integer('durasi')->nullable();
            $table->integer('denda')->nullable();
            $table->integer('total_bayar')->nullable();
            $table->enum('status', ['masuk', 'keluar'])->default('masuk');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
