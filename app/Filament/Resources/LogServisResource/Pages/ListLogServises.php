<?php

namespace App\Filament\Resources\LogServisResource\Pages;

use App\Exports\BiayaOperasionalExport;
use App\Filament\Resources\LogServisResource;
use App\Services\LaporanBiayaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ListLogServises extends ListRecords
{
    protected static string $resource = LogServisResource::class;

    protected function rentangForm(): array
    {
        return [
            DatePicker::make('dari')->label('Dari Tanggal')
                ->native(false)->default(now()->startOfMonth())->required(),
            DatePicker::make('sampai')->label('Sampai Tanggal')
                ->native(false)->default(now())->required()->afterOrEqual('dari'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Catat Servis'),

            // Ekspor laporan biaya operasional (BBM + servis Approved) — khusus Ketua Garasi.
            Actions\Action::make('exportExcel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->visible(fn () => auth()->user()?->isKetuaGarasi())
                ->modalHeading('Ekspor Laporan Biaya Operasional (Excel)')
                ->form($this->rentangForm())
                ->action(function (array $data) {
                    $dari = Carbon::parse($data['dari']);
                    $sampai = Carbon::parse($data['sampai']);

                    return Excel::download(
                        new BiayaOperasionalExport($dari, $sampai),
                        "laporan-biaya-operasional-{$dari->format('Ymd')}-{$sampai->format('Ymd')}.xlsx",
                    );
                }),

            Actions\Action::make('exportPdf')
                ->label('Ekspor PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->visible(fn () => auth()->user()?->isKetuaGarasi())
                ->modalHeading('Ekspor Laporan Biaya Operasional (PDF)')
                ->form($this->rentangForm())
                ->action(function (array $data) {
                    $dari = Carbon::parse($data['dari']);
                    $sampai = Carbon::parse($data['sampai']);
                    $rows = LaporanBiayaService::rows($dari, $sampai);

                    $pdf = Pdf::loadView('pdf.laporan-biaya', [
                        'rows' => $rows,
                        'dari' => $dari,
                        'sampai' => $sampai,
                        'total' => $rows->sum('biaya'),
                    ])->setPaper('a4')->output();

                    return response()->streamDownload(
                        fn () => print($pdf),
                        "laporan-biaya-operasional-{$dari->format('Ymd')}-{$sampai->format('Ymd')}.pdf",
                    );
                }),
        ];
    }
}
