<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LaporanMasalah extends Model
{
    use LogsActivity;

    protected $table = 'laporan_masalahs';

    protected $attributes = ['status' => 'Baru'];

    protected $fillable = ['armada_id', 'pelapor_id', 'judul', 'deskripsi', 'foto', 'status'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('laporan_masalah')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class)->withTrashed();
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelapor_id');
    }
}
