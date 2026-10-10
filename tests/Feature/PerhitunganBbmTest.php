<?php

namespace Tests\Feature;

use App\Models\LogBbm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class PerhitunganBbmTest extends TestCase
{
    use RefreshDatabase, MembuatData;

    public function test_rasio_efisiensi_dihitung_otomatis(): void
    {
        $armada = $this->buatArmada(['odometer_saat_ini' => 1000]);

        $log = $this->buatLogBbm($armada, $this->buatUser(), [
            'odometer_awal' => 1000, 'odometer_akhir' => 1100, 'jumlah_liter' => 10,
        ])->fresh();

        $this->assertEquals(10.00, (float) $log->rasio_efisiensi);
        $this->assertFalse($log->is_flagged_boros);
    }

    public function test_di_bawah_6_km_per_liter_ditandai_boros(): void
    {
        $armada = $this->buatArmada();

        $log = $this->buatLogBbm($armada, $this->buatUser(), [
            'odometer_awal' => 1000, 'odometer_akhir' => 1050, 'jumlah_liter' => 10,
        ])->fresh();

        $this->assertEquals(5.00, (float) $log->rasio_efisiensi);
        $this->assertTrue($log->is_flagged_boros);
    }

    public function test_tepat_6_km_per_liter_tidak_dianggap_boros(): void
    {
        $log = $this->buatLogBbm($this->buatArmada(), $this->buatUser(), [
            'odometer_awal' => 1000, 'odometer_akhir' => 1060, 'jumlah_liter' => 10,
        ])->fresh();

        $this->assertEquals(6.00, (float) $log->rasio_efisiensi);
        $this->assertFalse($log->is_flagged_boros);
    }

    public function test_5_99_km_per_liter_ditandai_boros(): void
    {
        $log = $this->buatLogBbm($this->buatArmada(), $this->buatUser(), [
            'odometer_awal' => 1000, 'odometer_akhir' => 1599, 'jumlah_liter' => 100,
        ])->fresh();

        $this->assertEquals(5.99, (float) $log->rasio_efisiensi);
        $this->assertTrue($log->is_flagged_boros);
    }

    public function test_odometer_armada_diperbarui_sesuai_odometer_akhir(): void
    {
        $armada = $this->buatArmada(['odometer_saat_ini' => 1000]);

        $this->buatLogBbm($armada, $this->buatUser(), ['odometer_awal' => 1000, 'odometer_akhir' => 1180]);

        $this->assertSame(1180, $armada->fresh()->odometer_saat_ini);
    }

    public function test_odometer_armada_tidak_mundur_oleh_catatan_lama(): void
    {
        $armada = $this->buatArmada(['odometer_saat_ini' => 5000]);

        $this->buatLogBbm($armada, $this->buatUser(), ['odometer_awal' => 1000, 'odometer_akhir' => 1100]);

        $this->assertSame(5000, $armada->fresh()->odometer_saat_ini);
    }

    public function test_odometer_awal_terisi_dari_armada_jika_kosong(): void
    {
        $armada = $this->buatArmada(['odometer_saat_ini' => 2000]);

        $log = LogBbm::create([
            'armada_id' => $armada->id,
            'driver_id' => $this->buatUser()->id,
            'tgl_pengisian' => now(),
            'odometer_akhir' => 2100,
            'jumlah_liter' => 10,
            'total_biaya' => 135000,
            'foto_nota' => 'nota-bbm/contoh.jpg',
        ])->fresh();

        $this->assertSame(2000, $log->odometer_awal);
        $this->assertEquals(10.00, (float) $log->rasio_efisiensi);
    }

    public function test_driver_terisi_otomatis_dari_pengguna_yang_login(): void
    {
        $driver = $this->buatUser();
        $armada = $this->buatArmada();

        $this->actingAs($driver);

        $log = LogBbm::create([
            'armada_id' => $armada->id,
            'odometer_awal' => 1000, 'odometer_akhir' => 1100,
            'jumlah_liter' => 10, 'total_biaya' => 135000,
            'foto_nota' => 'nota-bbm/contoh.jpg',
        ]);

        $this->assertSame($driver->id, $log->fresh()->driver_id);
    }

    public function test_mengubah_data_menghitung_ulang_rasio_dan_flag(): void
    {
        $log = $this->buatLogBbm($this->buatArmada(), $this->buatUser(), [
            'odometer_awal' => 1000, 'odometer_akhir' => 1100, 'jumlah_liter' => 10,
        ]);
        $this->assertFalse($log->fresh()->is_flagged_boros);

        $log->update(['jumlah_liter' => 20]); // 100 KM / 20 L = 5 KM/L

        $this->assertEquals(5.00, (float) $log->fresh()->rasio_efisiensi);
        $this->assertTrue($log->fresh()->is_flagged_boros);
    }

    public function test_liter_nol_tidak_error_dan_tidak_ditandai_boros(): void
    {
        $log = $this->buatLogBbm($this->buatArmada(), $this->buatUser(), [
            'odometer_awal' => 1000, 'odometer_akhir' => 1100, 'jumlah_liter' => 0,
        ])->fresh();

        $this->assertEquals(0.00, (float) $log->rasio_efisiensi);
        $this->assertFalse($log->is_flagged_boros);
    }
}
