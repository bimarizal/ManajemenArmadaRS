<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Carbon;

class Armada extends Model
{
    use SoftDeletes, LogsActivity;

    public const JENIS = [
        'Ambulans IGD' => 'Ambulans IGD',
        'Mobil Jenazah' => 'Mobil Jenazah',
        'Operasional Medis' => 'Operasional Medis',
        'Operasional Non-Medis' => 'Operasional Non-Medis',
    ];

    public const STATUS = [
        'Siap Pakai' => 'Siap Pakai',
        'Dalam Perawatan' => 'Dalam Perawatan',
        'Non-Aktif' => 'Non-Aktif',
    ];

    /** Batas hari untuk status "kuning" (mendekati jatuh tempo). */
    public const BATAS_PERINGATAN_HARI = 14;

    protected $table = 'armadas';

    protected $fillable = [
        'plat_nomor',
        'merk_tipe',
        'jenis_armada',
        'odometer_saat_ini',
        'tgl_jatuh_tempo_stnk',
        'tgl_jatuh_tempo_kir',
        'tgl_jatuh_tempo_pajak',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'odometer_saat_ini' => 'integer',
            'tgl_jatuh_tempo_stnk' => 'date',
            'tgl_jatuh_tempo_kir' => 'date',
            'tgl_jatuh_tempo_pajak' => 'date',
        ];
    }

    // ---------------------------------------------------------------- Relasi

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('armada')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function logBbms(): HasMany
    {
        return $this->hasMany(LogBbm::class);
    }

    public function logServises(): HasMany
    {
        return $this->hasMany(LogServis::class);
    }

    public function laporanMasalahs(): HasMany
    {
        return $this->hasMany(LaporanMasalah::class);
    }

    // ------------------------------------------------ Traffic Light Dokumen

    /**
     * Merah  : tanggal <= hari ini (kedaluwarsa)
     * Kuning : tanggal <= 14 hari ke depan
     * Hijau  : tanggal  > 14 hari ke depan
     */
    public static function statusDokumen(mixed $tanggal): string
    {
        if (blank($tanggal)) {
            return 'gray';
        }

        $tanggal = Carbon::parse($tanggal)->startOfDay();
        $hariIni = today();

        if ($tanggal->lte($hariIni)) {
            return 'danger';
        }

        if ($tanggal->lte($hariIni->copy()->addDays(self::BATAS_PERINGATAN_HARI))) {
            return 'warning';
        }

        return 'success';
    }

    public static function labelDokumen(mixed $tanggal): string
    {
        return match (self::statusDokumen($tanggal)) {
            'danger' => 'Kedaluwarsa',
            'warning' => 'Mendekati jatuh tempo (≤ 14 hari)',
            'success' => 'Aman',
            default => 'Tidak ada data',
        };
    }

    // ----------------------------------------------------------------- Scope

    /** Armada dengan salah satu dokumen (STNK/KIR/Pajak) merah atau kuning. */
    public function scopeDokumenMendekatiJatuhTempo(Builder $query): Builder
    {
        $batas = today()->addDays(self::BATAS_PERINGATAN_HARI)->toDateString();

        return $query->where(function (Builder $q) use ($batas) {
            $q->whereDate('tgl_jatuh_tempo_stnk', '<=', $batas)
                ->orWhereDate('tgl_jatuh_tempo_kir', '<=', $batas)
                ->orWhereDate('tgl_jatuh_tempo_pajak', '<=', $batas);
        });
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', '!=', 'Non-Aktif');
    }
}
