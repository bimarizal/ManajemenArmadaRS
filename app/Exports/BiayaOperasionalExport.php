<?php

namespace App\Exports;

use App\Services\LaporanBiayaService;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BiayaOperasionalExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(protected Carbon $dari, protected Carbon $sampai)
    {
    }

    public function headings(): array
    {
        return ['Tanggal', 'Kategori', 'Plat Nomor', 'Keterangan', 'Biaya (Rp)'];
    }

    public function array(): array
    {
        $rows = LaporanBiayaService::rows($this->dari, $this->sampai);

        $data = $rows->map(fn (array $r) => [
            $r['tanggal'], $r['kategori'], $r['plat'], $r['keterangan'], $r['biaya'],
        ])->all();

        $data[] = ['', '', '', 'TOTAL', $rows->sum('biaya')];

        return $data;
    }
}
