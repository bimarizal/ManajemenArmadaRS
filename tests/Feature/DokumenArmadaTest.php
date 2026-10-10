<?php

namespace Tests\Feature;

use App\Models\Armada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class DokumenArmadaTest extends TestCase
{
    use RefreshDatabase, MembuatData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-04 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_traffic_light_merah_kuning_hijau(): void
    {
        $this->assertSame('danger', Armada::statusDokumen('2026-10-03'));  // kemarin
        $this->assertSame('danger', Armada::statusDokumen('2026-10-04'));  // hari ini
        $this->assertSame('warning', Armada::statusDokumen('2026-10-05')); // besok
        $this->assertSame('warning', Armada::statusDokumen('2026-10-18')); // tepat 14 hari
        $this->assertSame('success', Armada::statusDokumen('2026-10-19')); // 15 hari
        $this->assertSame('gray', Armada::statusDokumen(null));
    }

    public function test_scope_dokumen_mendekati_jatuh_tempo(): void
    {
        $aman = $this->buatArmada();
        $stnkKuning = $this->buatArmada(['tgl_jatuh_tempo_stnk' => today()->addDays(14)->toDateString()]);
        $kirMerah = $this->buatArmada(['tgl_jatuh_tempo_kir' => today()->subDay()->toDateString()]);
        $pajakBatas = $this->buatArmada(['tgl_jatuh_tempo_pajak' => today()->addDays(15)->toDateString()]);

        $ids = Armada::dokumenMendekatiJatuhTempo()->pluck('id');

        $this->assertTrue($ids->contains($stnkKuning->id));
        $this->assertTrue($ids->contains($kirMerah->id));
        $this->assertFalse($ids->contains($aman->id));
        $this->assertFalse($ids->contains($pajakBatas->id)); // 15 hari = hijau
    }

    public function test_armada_non_aktif_tidak_dihitung_pada_peringatan(): void
    {
        $nonAktif = $this->buatArmada([
            'status' => 'Non-Aktif',
            'tgl_jatuh_tempo_stnk' => today()->subDays(10)->toDateString(),
        ]);

        $this->assertFalse(Armada::aktif()->dokumenMendekatiJatuhTempo()->pluck('id')->contains($nonAktif->id));
    }
}
