<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_servises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('armada_id')->constrained('armadas')->cascadeOnDelete();
            $table->date('tgl_servis');
            $table->string('jenis_servis');
            $table->string('bengkel');
            $table->decimal('biaya', 12, 2);
            $table->unsignedInteger('odometer_servis');
            $table->text('deskripsi')->nullable();
            $table->string('foto_kwitansi')->nullable();
            $table->enum('status_approval', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status_approval');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_servises');
    }
};
