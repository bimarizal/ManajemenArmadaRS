<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LogBbmResource;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard Eksekutif';

    protected static ?string $navigationLabel = 'Dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->isDriver() ?? true);
    }

    // Driver tidak punya dashboard: langsung diarahkan ke Log BBM.
    public function mount(): void
    {
        if (auth()->user()?->isDriver()) {
            $this->redirect(LogBbmResource::getUrl());
        }
    }
}
