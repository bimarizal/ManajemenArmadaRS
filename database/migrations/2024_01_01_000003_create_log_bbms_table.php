<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_bbms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('armada_id')->constrained('armadas')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('tgl_pengisian');
            $table->unsignedInteger('odometer_awal');
            $table->unsignedInteger('odometer_akhir');
            $table->decimal('jumlah_liter', 8, 2);
            $table->decimal('total_biaya', 12, 2);
            $table->string('foto_nota');
            $table->decimal('rasio_efisiensi', 8, 2)->default(0);   // (akhir - awal) / liter
            $table->boolean('is_flagged_boros')->default(false);    // true jika rasio < 6 KM/L
            $table->timestamps();

            $table->index('tgl_pengisian');
            $table->index(['armada_id', 'tgl_pengisian']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_bbms');
    }
};
