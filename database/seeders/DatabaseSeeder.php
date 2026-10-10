<?php

namespace Database\Seeders;

use App\Models\Armada;
use App\Models\LaporanMasalah;
use App\Models\LogBbm;
use App\Models\LogServis;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Seeder ini berisi akun demo dan tidak boleh dijalankan di production. Gunakan: php artisan fleet:buat-akun');

            return;
        }

        $this->buatBerkasContoh();

        // ------------------------------------------------------------ Users
        // Semua password: "password"
        $ketua = User::updateOrCreate(['email' => 'ketua@simfleet.test'], [
            'name' => 'dr. Hendra Kusuma', 'password' => 'password', 'role' => User::ROLE_KETUA_GARASI,
        ]);
        $admin = User::updateOrCreate(['email' => 'admin@simfleet.test'], [
            'name' => 'Siti Rahmawati', 'password' => 'password', 'role' => User::ROLE_ADMIN_GARASI,
        ]);
        $driver1 = User::updateOrCreate(['email' => 'driver1@simfleet.test'], [
            'name' => 'Budi Santoso', 'password' => 'password', 'role' => User::ROLE_DRIVER,
        ]);
        $driver2 = User::updateOrCreate(['email' => 'driver2@simfleet.test'], [
            'name' => 'Agus Wibowo', 'password' => 'password', 'role' => User::ROLE_DRIVER,
        ]);
        $drivers = [$driver1, $driver2];

        // ----------------------------------------------------------- Armada
        // Tanggal sengaja bervariasi agar semua warna traffic light muncul.
        $data = [
            [
                'plat_nomor' => 'AD 1001 RS', 'merk_tipe' => 'Toyota HiAce Commuter / Ambulans',
                'jenis_armada' => 'Ambulans IGD', 'odometer_saat_ini' => 48250, 'status' => 'Siap Pakai',
                'stnk' => 120, 'kir' => 9, 'pajak' => 200,           // KIR kuning
            ],
            [
                'plat_nomor' => 'AD 1002 RS', 'merk_tipe' => 'Isuzu Elf / Ambulans',
                'jenis_armada' => 'Ambulans IGD', 'odometer_saat_ini' => 91340, 'status' => 'Dalam Perawatan',
                'stnk' => 45, 'kir' => -5, 'pajak' => 60,            // KIR merah
            ],
            [
                'plat_nomor' => 'AD 2001 RS', 'merk_tipe' => 'Daihatsu Gran Max / Mobil Jenazah',
                'jenis_armada' => 'Mobil Jenazah', 'odometer_saat_ini' => 63780, 'status' => 'Siap Pakai',
                'stnk' => 240, 'kir' => 150, 'pajak' => 240,         // semua hijau
            ],
            [
                'plat_nomor' => 'AD 3001 RS', 'merk_tipe' => 'Toyota Innova Reborn / Operasional Medis',
                'jenis_armada' => 'Operasional Medis', 'odometer_saat_ini' => 30120, 'status' => 'Siap Pakai',
                'stnk' => 14, 'kir' => 80, 'pajak' => 14,            // tepat 14 hari = kuning
            ],
            [
                'plat_nomor' => 'AD 4001 RS', 'merk_tipe' => 'Mitsubishi Xpander / Operasional Non-Medis',
                'jenis_armada' => 'Operasional Non-Medis', 'odometer_saat_ini' => 22460, 'status' => 'Siap Pakai',
                'stnk' => 300, 'kir' => 100, 'pajak' => 0,           // pajak jatuh tempo hari ini = merah
            ],
        ];

        $armadas = [];
        foreach ($data as $a) {
            $armadas[] = Armada::updateOrCreate(['plat_nomor' => $a['plat_nomor']], [
                'merk_tipe' => $a['merk_tipe'],
                'jenis_armada' => $a['jenis_armada'],
                'odometer_saat_ini' => $a['odometer_saat_ini'],
                'status' => $a['status'],
                'tgl_jatuh_tempo_stnk' => today()->addDays($a['stnk'])->toDateString(),
                'tgl_jatuh_tempo_kir' => today()->addDays($a['kir'])->toDateString(),
                'tgl_jatuh_tempo_pajak' => today()->addDays($a['pajak'])->toDateString(),
            ]);
        }

        // ---------------------------------------------------------- Log BBM
        // Target konsumsi (KM/L) per armada. AD 1002 sengaja boros (< 6).
        $konsumsi = [9.2, 4.8, 8.5, 11.0, 10.5];

        foreach ($armadas as $idx => $armada) {
            for ($i = 0; $i < 8; $i++) {
                $armada->refresh();

                $liter = 20 + $i + ($idx * 2);
                $jarak = (int) round($liter * $konsumsi[$idx]) + ($i % 2 === 0 ? 4 : -3);
                $awal = $armada->odometer_saat_ini;

                LogBbm::create([
                    'armada_id' => $armada->id,
                    'driver_id' => $drivers[($idx + $i) % 2]->id,
                    'tgl_pengisian' => now()->subDays((7 - $i) * 2)->setTime(7 + ($i % 6), 20),
                    'odometer_awal' => $awal,
                    'odometer_akhir' => $awal + $jarak,
                    'jumlah_liter' => $liter,
                    'total_biaya' => $liter * 13500,
                    'foto_nota' => 'nota-bbm/nota-contoh.svg',
                ]);
            }
        }

        // -------------------------------------------------------- Log Servis
        $armadas[0]->refresh();
        LogServis::create([
            'armada_id' => $armadas[0]->id, 'tgl_servis' => today()->subDays(20),
            'jenis_servis' => 'Ganti Oli & Cek Rem', 'bengkel' => 'Bengkel Resmi Toyota Solo',
            'biaya' => 850000, 'odometer_servis' => $armadas[0]->odometer_saat_ini - 600,
            'deskripsi' => 'Ganti oli mesin, filter oli, pemeriksaan kampas rem.',
            'foto_kwitansi' => 'kwitansi-servis/kwitansi-contoh.svg',
            'status_approval' => 'Approved', 'approved_by' => $ketua->id, // <= Rp 1 juta
        ]);

        // > Rp 1 juta: model memaksa Pending (menunggu Ketua Garasi).
        LogServis::create([
            'armada_id' => $armadas[1]->id, 'tgl_servis' => today()->subDays(2),
            'jenis_servis' => 'Overhaul Rem & Kampas', 'bengkel' => 'Bengkel Isuzu Surakarta',
            'biaya' => 4750000, 'odometer_servis' => $armadas[1]->refresh()->odometer_saat_ini,
            'deskripsi' => 'Rem depan blong, perlu penggantian master rem dan kampas.',
            'foto_kwitansi' => 'kwitansi-servis/kwitansi-contoh.svg',
        ]);

        $servisApproved = LogServis::create([
            'armada_id' => $armadas[2]->id, 'tgl_servis' => today()->subDays(10),
            'jenis_servis' => 'Service Berkala 60.000 KM', 'bengkel' => 'Daihatsu Solo Baru',
            'biaya' => 1350000, 'odometer_servis' => $armadas[2]->refresh()->odometer_saat_ini - 300,
            'deskripsi' => 'Service besar, ganti oli gardan dan busi.',
            'foto_kwitansi' => 'kwitansi-servis/kwitansi-contoh.svg',
        ]);
        $servisApproved->update(['status_approval' => 'Approved', 'approved_by' => $ketua->id]);

        $servisRejected = LogServis::create([
            'armada_id' => $armadas[3]->id, 'tgl_servis' => today()->subDays(6),
            'jenis_servis' => 'Ganti Aki', 'bengkel' => 'Toko Aki Sejahtera',
            'biaya' => 1150000, 'odometer_servis' => $armadas[3]->refresh()->odometer_saat_ini,
            'deskripsi' => 'Aki original, harga di atas pembanding.',
        ]);
        $servisRejected->update(['status_approval' => 'Rejected', 'approved_by' => $ketua->id]);

        LogServis::create([
            'armada_id' => $armadas[4]->id, 'tgl_servis' => today()->subDays(15),
            'jenis_servis' => 'Tune Up Ringan', 'bengkel' => 'Bengkel Mitra Motor',
            'biaya' => 450000, 'odometer_servis' => $armadas[4]->refresh()->odometer_saat_ini - 500,
            'status_approval' => 'Approved', 'approved_by' => $ketua->id,
        ]);

        // ------------------------------------------------- Laporan Masalah
        LaporanMasalah::create([
            'armada_id' => $armadas[1]->id, 'pelapor_id' => $driver1->id,
            'judul' => 'Rem bermasalah',
            'deskripsi' => 'Pedal rem terasa dalam dan terdengar berdecit saat pengereman.',
            'status' => 'Diproses',
        ]);
        LaporanMasalah::create([
            'armada_id' => $armadas[0]->id, 'pelapor_id' => $driver2->id,
            'judul' => 'AC tidak dingin',
            'deskripsi' => 'AC kabin pasien kurang dingin sejak kemarin.',
            'status' => 'Baru',
        ]);
    }

    /** SVG sederhana sebagai pengganti foto nota/kwitansi pada data dummy. */
    private function buatBerkasContoh(): void
    {
        $svg = fn (string $judul) => '<svg xmlns="http://www.w3.org/2000/svg" width="360" height="480" viewBox="0 0 360 480">'
            . '<rect width="360" height="480" fill="#f8fafc" stroke="#94a3b8"/>'
            . '<text x="180" y="60" font-family="monospace" font-size="20" text-anchor="middle" fill="#0f172a">' . $judul . '</text>'
            . '<text x="180" y="100" font-family="monospace" font-size="13" text-anchor="middle" fill="#64748b">(data contoh SIM-Fleet RS)</text>'
            . '<g stroke="#cbd5e1"><line x1="30" y1="150" x2="330" y2="150"/><line x1="30" y1="190" x2="330" y2="190"/>'
            . '<line x1="30" y1="230" x2="330" y2="230"/><line x1="30" y1="270" x2="330" y2="270"/></g></svg>';

        Storage::disk('local')->put('nota-bbm/nota-contoh.svg', $svg('NOTA SPBU'));
        Storage::disk('local')->put('kwitansi-servis/kwitansi-contoh.svg', $svg('KWITANSI SERVIS'));
    }
}
