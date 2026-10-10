<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('armadas', function (Blueprint $table) {
            $table->id();
            $table->string('plat_nomor')->unique();
            $table->string('merk_tipe');
            $table->enum('jenis_armada', [
                'Ambulans IGD',
                'Mobil Jenazah',
                'Operasional Medis',
                'Operasional Non-Medis',
            ]);
            $table->unsignedInteger('odometer_saat_ini')->default(0); // KM
            $table->date('tgl_jatuh_tempo_stnk');
            $table->date('tgl_jatuh_tempo_kir');
            $table->date('tgl_jatuh_tempo_pajak');
            $table->enum('status', ['Siap Pakai', 'Dalam Perawatan', 'Non-Aktif'])->default('Siap Pakai');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armadas');
    }
};
