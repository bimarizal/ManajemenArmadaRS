<?php

namespace Tests\Feature;

use App\Models\Armada;
use App\Models\LogBbm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase, MembuatData;

    public function test_menghapus_armada_tidak_menghapus_riwayat_bbm_dan_servis(): void
    {
        $armada = $this->buatArmada();
        $log = $this->buatLogBbm($armada, $this->buatUser());
        $servis = $this->buatLogServis($armada);

        $armada->delete();

        $this->assertSame(0, Armada::count());
        $this->assertSame(1, Armada::withTrashed()->count());
        $this->assertNotNull(LogBbm::find($log->id));
        $this->assertNotNull($servis->fresh());
    }

    public function test_riwayat_tetap_dapat_menampilkan_plat_armada_yang_terhapus(): void
    {
        $armada = $this->buatArmada(['plat_nomor' => 'AD 9999 XX']);
        $log = $this->buatLogBbm($armada, $this->buatUser());

        $armada->delete();

        $this->assertSame('AD 9999 XX', $log->fresh()->armada->plat_nomor);
    }

    public function test_armada_dapat_dipulihkan(): void
    {
        $armada = $this->buatArmada();
        $armada->delete();

        $armada->restore();

        $this->assertSame(1, Armada::count());
    }

    public function test_log_bbm_dapat_dihapus_dan_dipulihkan(): void
    {
        $log = $this->buatLogBbm($this->buatArmada(), $this->buatUser());

        $log->delete();
        $this->assertSame(0, LogBbm::count());

        $log->restore();
        $this->assertSame(1, LogBbm::count());
    }
}
