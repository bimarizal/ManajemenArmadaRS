<?php

namespace App\Services;

use App\Models\LogBbm;
use App\Models\LogServis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LaporanBiayaService
{
    /**
     * Gabungan biaya BBM + servis (hanya yang Approved) dalam rentang tanggal.
     *
     * @return Collection<int, array{tanggal:string,kategori:string,plat:?string,keterangan:string,biaya:float}>
     */
    public static function rows(Carbon $dari, Carbon $sampai): Collection
    {
        $bbm = LogBbm::with('armada')
            ->whereBetween('tgl_pengisian', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->get()
            ->map(fn (LogBbm $l) => [
                'tanggal' => $l->tgl_pengisian->format('Y-m-d'),
                'kategori' => 'BBM',
                'plat' => $l->armada?->plat_nomor,
                'keterangan' => number_format((float) $l->jumlah_liter, 2, ',', '.') . ' L (' . number_format((float) $l->rasio_efisiensi, 2, ',', '.') . ' KM/L)',
                'biaya' => (float) $l->total_biaya,
            ]);

        $servis = LogServis::with('armada')
            ->where('status_approval', 'Approved')
            ->whereBetween('tgl_servis', [$dari->toDateString(), $sampai->toDateString()])
            ->get()
            ->map(fn (LogServis $s) => [
                'tanggal' => $s->tgl_servis->format('Y-m-d'),
                'kategori' => 'Servis',
                'plat' => $s->armada?->plat_nomor,
                'keterangan' => $s->jenis_servis . ' - ' . $s->bengkel,
                'biaya' => (float) $s->biaya,
            ]);

        return $bbm->concat($servis)->sortBy('tanggal')->values();
    }
}
