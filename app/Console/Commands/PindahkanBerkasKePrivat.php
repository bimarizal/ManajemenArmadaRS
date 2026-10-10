<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PindahkanBerkasKePrivat extends Command
{
    protected $signature = 'berkas:pindahkan-ke-privat
                            {--hapus-asli : Hapus salinan di disk publik setelah berhasil disalin}';

    protected $description = 'Menyalin foto nota/kwitansi/laporan lama dari disk publik ke disk privat';

    /** [tabel, kolom] yang menyimpan path berkas. */
    private const SUMBER = [
        ['log_bbms', 'foto_nota'],
        ['log_servises', 'foto_kwitansi'],
        ['laporan_masalahs', 'foto'],
    ];

    public function handle(): int
    {
        $publik = Storage::disk('public');
        $privat = Storage::disk('local');
        $hapusAsli = (bool) $this->option('hapus-asli');

        $disalin = $sudahAda = $hilang = $dihapus = 0;

        foreach (self::SUMBER as [$tabel, $kolom]) {
            // DB::table() sengaja dipakai agar data yang di-soft-delete ikut terproses.
            $daftar = DB::table($tabel)->whereNotNull($kolom)->distinct()->pluck($kolom);

            foreach ($daftar as $path) {
                if ($privat->exists($path)) {
                    $sudahAda++;
                } elseif ($publik->exists($path)) {
                    $privat->writeStream($path, $publik->readStream($path));
                    $disalin++;
                } else {
                    $hilang++;
                    $this->warn("Berkas tidak ditemukan di disk publik: {$path}");

                    continue;
                }

                if ($hapusAsli && $publik->exists($path) && $privat->exists($path)) {
                    $publik->delete($path);
                    $dihapus++;
                }
            }
        }

        $this->info("Selesai. Disalin: {$disalin}, sudah ada di privat: {$sudahAda}, tidak ditemukan: {$hilang}, asli dihapus: {$dihapus}.");

        return self::SUCCESS;
    }
}
