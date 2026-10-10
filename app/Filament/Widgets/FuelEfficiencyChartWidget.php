<?php

namespace App\Filament\Widgets;

use App\Models\LogBbm;
use Filament\Widgets\ChartWidget;

class FuelEfficiencyChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Efisiensi BBM Harian per Armada (KM/L)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '360px';

    public ?string $filter = '14';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->isKetuaGarasi() || $user->isAdminGarasi());
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 hari terakhir',
            '14' => '14 hari terakhir',
            '30' => '30 hari terakhir',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $hari = (int) ($this->filter ?: 14);
        $mulai = today()->subDays($hari - 1);

        $tanggal = [];
        $labels = [];
        for ($i = 0; $i < $hari; $i++) {
            $d = $mulai->copy()->addDays($i);
            $tanggal[] = $d->toDateString();
            $labels[] = $d->format('d/m');
        }

        $perArmada = LogBbm::with('armada:id,plat_nomor')
            ->where('tgl_pengisian', '>=', $mulai->copy()->startOfDay())
            ->orderBy('tgl_pengisian')
            ->get()
            ->groupBy('armada_id');

        $palet = ['#0d9488', '#2563eb', '#d97706', '#7c3aed', '#db2777', '#059669', '#4b5563', '#0891b2'];
        $datasets = [];
        $urut = 0;

        foreach ($perArmada as $logs) {
            // Rata-rata efisiensi per hari (jika ada >1 pengisian pada hari yang sama).
            $perHari = $logs
                ->groupBy(fn (LogBbm $l) => $l->tgl_pengisian->toDateString())
                ->map(fn ($grup) => round($grup->avg(fn (LogBbm $l) => (float) $l->rasio_efisiensi), 2));

            $warna = $palet[$urut % count($palet)];
            $urut++;

            $datasets[] = [
                'label' => $logs->first()->armada?->plat_nomor ?? '-',
                'data' => collect($tanggal)->map(fn (string $t) => $perHari->get($t))->all(),
                'borderColor' => $warna,
                'backgroundColor' => $warna,
                'tension' => 0.3,
                'spanGaps' => true,
                'pointRadius' => 4,
            ];
        }

        // Garis batas boros.
        $datasets[] = [
            'label' => 'Batas Boros (' . LogBbm::AMBANG_BOROS . ' KM/L)',
            'data' => array_fill(0, $hari, LogBbm::AMBANG_BOROS),
            'borderColor' => '#dc2626',
            'backgroundColor' => '#dc2626',
            'borderDash' => [6, 6],
            'borderWidth' => 2,
            'pointRadius' => 0,
            'fill' => false,
        ];

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'KM / Liter'],
                ],
            ],
        ];
    }
}
