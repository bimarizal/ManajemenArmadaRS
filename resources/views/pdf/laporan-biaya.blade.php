<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Biaya Operasional</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p { margin: 0; color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #999; padding: 5px 6px; }
        th { background: #e5e7eb; text-align: left; }
        .r { text-align: right; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Laporan Biaya Operasional Armada</h1>
    <p>SIM-Fleet RS &middot; Periode {{ $dari->translatedFormat('d F Y') }} s/d {{ $sampai->translatedFormat('d F Y') }}</p>
    <p>Biaya servis yang dimasukkan hanya yang berstatus Approved.</p>

    <table>
        <thead>
            <tr>
                <th>No</th><th>Tanggal</th><th>Kategori</th><th>Plat Nomor</th><th>Keterangan</th><th class="r">Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($r['tanggal'])->format('d/m/Y') }}</td>
                    <td>{{ $r['kategori'] }}</td>
                    <td>{{ $r['plat'] }}</td>
                    <td>{{ $r['keterangan'] }}</td>
                    <td class="r">{{ \App\Support\Rupiah::format($r['biaya']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center">Tidak ada data pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="r">Total BBM</td><td class="r">{{ \App\Support\Rupiah::format($rows->where('kategori', 'BBM')->sum('biaya')) }}</td></tr>
            <tr><td colspan="5" class="r">Total Servis</td><td class="r">{{ \App\Support\Rupiah::format($rows->where('kategori', 'Servis')->sum('biaya')) }}</td></tr>
            <tr><td colspan="5" class="r">TOTAL BIAYA OPERASIONAL</td><td class="r">{{ \App\Support\Rupiah::format($total) }}</td></tr>
        </tfoot>
    </table>
</body>
</html>
