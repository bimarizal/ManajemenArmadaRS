<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabel tambahan untuk fitur "Laporkan Kendaraan Bermasalah" (akses Driver).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_masalahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('armada_id')->constrained('armadas')->cascadeOnDelete();
            $table->foreignId('pelapor_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('foto')->nullable();
            $table->enum('status', ['Baru', 'Diproses', 'Selesai'])->default('Baru');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_masalahs');
    }
};
