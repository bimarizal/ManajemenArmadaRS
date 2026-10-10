<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LogServis extends Model
{
    use SoftDeletes, LogsActivity;

    /** Biaya di atas angka ini wajib melalui persetujuan Ketua Garasi. */
    public const BATAS_APPROVAL = 1_000_000;

    protected $table = 'log_servises';

    protected $attributes = [
        'status_approval' => 'Pending',
    ];

    protected $fillable = [
        'armada_id',
        'tgl_servis',
        'jenis_servis',
        'bengkel',
        'biaya',
        'odometer_servis',
        'deskripsi',
        'foto_kwitansi',
        'status_approval',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_servis' => 'date',
            'biaya' => 'decimal:2',
            'odometer_servis' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Biaya > Rp 1.000.000 (baru atau diubah) => wajib kembali ke 'Pending'.
        static::saving(function (LogServis $servis): void {
            if ((float) $servis->biaya > self::BATAS_APPROVAL && $servis->isDirty('biaya')) {
                $servis->status_approval = 'Pending';
                $servis->approved_by = null;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('servis')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
