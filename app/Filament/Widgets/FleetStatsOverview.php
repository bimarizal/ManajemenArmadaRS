<?php

namespace App\Filament\Widgets;

use App\Models\Armada;
use App\Models\LogBbm;
use App\Support\Rupiah;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FleetStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->isKetuaGarasi() || $user->isAdminGarasi());
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $awal = now()->startOfMonth();
        $akhir = now()->endOfMonth();

        $total = Armada::count();
        $siap = Armada::where('status', 'Siap Pakai')->count();
        $perawatan = Armada::where('status', 'Dalam Perawatan')->count();

        $biayaBbm = LogBbm::whereBetween('tgl_pengisian', [$awal, $akhir])->sum('total_biaya');

        $logBoros = LogBbm::where('is_flagged_boros', true)->whereBetween('tgl_pengisian', [$awal, $akhir]);
        $jumlahPengisianBoros = (clone $logBoros)->count();
        $armadaBoros = (clone $logBoros)->distinct()->count('armada_id');

        $perluPerpanjangan = Armada::aktif()->dokumenMendekatiJatuhTempo()->count();

        return [
            Stat::make('Armada Siap Pakai', "{$siap} dari {$total}")
                ->description("{$perawatan} armada dalam perawatan")
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color($perawatan > 0 ? 'warning' : 'success'),

            Stat::make('Pengeluaran BBM Bulan Ini', Rupiah::format($biayaBbm))
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Armada Boros (Flagged)', $armadaBoros)
                ->description("{$jumlahPengisianBoros} pengisian < 6 KM/L bulan ini")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($armadaBoros > 0 ? 'danger' : 'success'),

            Stat::make('Dokumen Perlu Perpanjangan', $perluPerpanjangan)
                ->description('STNK / KIR / Pajak ≤ 14 hari atau kedaluwarsa')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($perluPerpanjangan > 0 ? 'warning' : 'success'),
        ];
    }
}
