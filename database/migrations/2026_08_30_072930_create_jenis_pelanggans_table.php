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
        Schema::create('jenis_pelanggans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->boolean('is_gratis_parkir')->default(false);
            $table->boolean('is_bebas_denda')->default(false);
            $table->integer('prioritas_level')->default(1);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->boolean('denda')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_pelanggans');
    }
};
