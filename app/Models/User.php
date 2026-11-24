<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, LogsActivity;

    public const ROLE_DRIVER = 'driver';
    public const ROLE_PENANGGUNGJAWAB_GARASI = 'penanggungjawab_garasi';
    public const ROLE_KASUBAG_RUMAHTANGGA = 'kasubag_rumahtangga';

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active && in_array($this->role, [
            self::ROLE_DRIVER,
            self::ROLE_PENANGGUNGJAWAB_GARASI,
            self::ROLE_KASUBAG_RUMAHTANGGA,
        ], true);
    }

    public function isDriver(): bool
    {
        return $this->role === self::ROLE_DRIVER;
    }

    public function isPenanggungjawabGarasi(): bool
    {
        return $this->role === self::ROLE_PENANGGUNGJAWAB_GARASI;
    }

    public function isKasubagRumahtangga(): bool
    {
        return $this->role === self::ROLE_KASUBAG_RUMAHTANGGA;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('pengguna')
            ->logOnly(['name', 'email', 'role', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function logBbms(): HasMany
    {
        return $this->hasMany(LogBbm::class, 'driver_id');
    }
}
