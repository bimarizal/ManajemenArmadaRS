<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LogBbm extends Model
{
    use SoftDeletes, LogsActivity;

    /** Rasio di bawah angka ini (KM/Liter) ditandai boros. */
    public const AMBANG_BOROS = 6.00;

    protected $table = 'log_bbms';

    protected $fillable = [
        'armada_id',
        'driver_id',
        'tgl_pengisian',
        'odometer_awal',
        'odometer_akhir',
        'jumlah_liter',
        'total_biaya',
        'foto_nota',
        'rasio_efisiensi',
        'is_flagged_boros',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pengisian' => 'datetime',
            'odometer_awal' => 'integer',
            'odometer_akhir' => 'integer',
            'jumlah_liter' => 'decimal:2',
            'total_biaya' => 'decimal:2',
            'rasio_efisiensi' => 'decimal:2',
            'is_flagged_boros' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Urutan event Eloquent: saving -> creating -> (insert) -> created -> saved

        // Hitung rasio efisiensi & flag boros setiap kali disimpan (create maupun update).
        static::saving(function (LogBbm $log): void {
            if (! $log->exists && blank($log->odometer_awal)) {
                $log->odometer_awal = (int) Armada::whereKey($log->armada_id)->value('odometer_saat_ini');
            }

            $jarak = (int) $log->odometer_akhir - (int) $log->odometer_awal;
            $liter = (float) $log->jumlah_liter;
            $rasio = $liter > 0 ? round($jarak / $liter, 2) : 0.0;

            $log->rasio_efisiensi = $rasio;
            $log->is_flagged_boros = $liter > 0 && $rasio < self::AMBANG_BOROS;
        });

        // Default untuk record baru.
        static::creating(function (LogBbm $log): void {
            if (blank($log->driver_id) && auth()->check()) {
                $log->driver_id = auth()->id();
            }

            $log->tgl_pengisian ??= now();
        });

        // Sinkronkan odometer armada dengan odometer_akhir terbaru.
        static::created(function (LogBbm $log): void {
            Armada::whereKey($log->armada_id)
                ->where('odometer_saat_ini', '<', $log->odometer_akhir)
                ->update(['odometer_saat_ini' => $log->odometer_akhir]);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('bbm')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class)->withTrashed();
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
