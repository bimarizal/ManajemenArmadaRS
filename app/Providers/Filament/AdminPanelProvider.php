<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\SecurityHeaders;
use App\Filament\Widgets\ExpiringDocumentsTable;
use App\Filament\Widgets\FleetStatsOverview;
use App\Filament\Widgets\FuelEfficiencyChartWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->passwordReset()
            ->brandName('SIM-Fleet RS')
            ->colors(['primary' => Color::Teal])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups(['Operasional', 'Master Data', 'Sistem'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([Dashboard::class])
            ->widgets([
                FleetStatsOverview::class,
                ExpiringDocumentsTable::class,
                FuelEfficiencyChartWidget::class,
            ])
            // Highlight merah untuk baris Log BBM yang ditandai boros.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(
                    '<style>
                        tr.row-boros { background-color: rgb(254 226 226 / .65) !important; }
                        .dark tr.row-boros { background-color: rgb(127 29 29 / .35) !important; }
                    </style>'
                ),
            )
            ->middleware([
                SecurityHeaders::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
