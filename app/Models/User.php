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
    public const ROLE_ADMIN_GARASI = 'admin_garasi';
    public const ROLE_KETUA_GARASI = 'ketua_garasi';

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
            self::ROLE_ADMIN_GARASI,
            self::ROLE_KETUA_GARASI,
        ], true);
    }

    public function isDriver(): bool
    {
        return $this->role === self::ROLE_DRIVER;
    }

    public function isAdminGarasi(): bool
    {
        return $this->role === self::ROLE_ADMIN_GARASI;
    }

    public function isKetuaGarasi(): bool
    {
        return $this->role === self::ROLE_KETUA_GARASI;
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
