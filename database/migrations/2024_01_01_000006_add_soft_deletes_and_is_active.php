<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['armadas', 'log_bbms', 'log_servises'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        foreach (['armadas', 'log_bbms', 'log_servises'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
