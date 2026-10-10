<?php

namespace Tests\Concerns;

use App\Models\Armada;
use App\Models\LogBbm;
use App\Models\LogServis;
use App\Models\User;
use Illuminate\Support\Str;

trait MembuatData
{
    protected function buatUser(string $role = User::ROLE_DRIVER, array $atribut = []): User
    {
        return User::create(array_merge([
            'name' => 'Pengguna ' . Str::random(5),
            'email' => Str::lower(Str::random(10)) . '@test.local',
            'password' => 'password',
            'role' => $role,
        ], $atribut));
    }

    protected function buatArmada(array $atribut = []): Armada
    {
        return Armada::create(array_merge([
            'plat_nomor' => 'AD ' . Str::upper(Str::random(5)),
            'merk_tipe' => 'Toyota HiAce / Ambulans',
            'jenis_armada' => 'Ambulans IGD',
            'odometer_saat_ini' => 1000,
            'tgl_jatuh_tempo_stnk' => today()->addYear()->toDateString(),
            'tgl_jatuh_tempo_kir' => today()->addYear()->toDateString(),
            'tgl_jatuh_tempo_pajak' => today()->addYear()->toDateString(),
            'status' => 'Siap Pakai',
        ], $atribut));
    }

    protected function buatLogBbm(Armada $armada, User $driver, array $atribut = []): LogBbm
    {
        return LogBbm::create(array_merge([
            'armada_id' => $armada->id,
            'driver_id' => $driver->id,
            'tgl_pengisian' => now(),
            'odometer_awal' => 1000,
            'odometer_akhir' => 1100,
            'jumlah_liter' => 10,
            'total_biaya' => 135000,
            'foto_nota' => 'nota-bbm/contoh.jpg',
        ], $atribut));
    }

    protected function buatLogServis(Armada $armada, array $atribut = []): LogServis
    {
        return LogServis::create(array_merge([
            'armada_id' => $armada->id,
            'tgl_servis' => today()->toDateString(),
            'jenis_servis' => 'Ganti Oli & Cek Rem',
            'bengkel' => 'Bengkel Uji',
            'biaya' => 500000,
            'odometer_servis' => 1000,
        ], $atribut));
    }
}
