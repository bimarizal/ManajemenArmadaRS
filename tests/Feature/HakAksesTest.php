<?php

namespace Tests\Feature;

use App\Models\Armada;
use App\Models\LaporanMasalah;
use App\Models\LogBbm;
use App\Models\LogServis;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class HakAksesTest extends TestCase
{
    use RefreshDatabase, MembuatData;

    public function test_driver_hanya_boleh_bbm_dan_laporan_masalah(): void
    {
        $driver = $this->buatUser();

        $this->assertFalse($driver->can('viewAny', Armada::class));
        $this->assertFalse($driver->can('viewAny', LogServis::class));
        $this->assertFalse($driver->can('viewAny', User::class));
        $this->assertTrue($driver->can('viewAny', LogBbm::class));
        $this->assertTrue($driver->can('create', LogBbm::class));
        $this->assertTrue($driver->can('create', LaporanMasalah::class));
    }

    public function test_driver_hanya_boleh_melihat_log_bbm_miliknya(): void
    {
        $armada = $this->buatArmada();
        $driverA = $this->buatUser();
        $driverB = $this->buatUser();
        $logA = $this->buatLogBbm($armada, $driverA);
        $logB = $this->buatLogBbm($armada, $driverB);

        $this->assertTrue($driverA->can('view', $logA));
        $this->assertFalse($driverA->can('view', $logB));
    }

    public function test_driver_tidak_boleh_mengubah_atau_menghapus_log_bbm(): void
    {
        $driver = $this->buatUser();
        $log = $this->buatLogBbm($this->buatArmada(), $driver);

        $this->assertFalse($driver->can('update', $log));
        $this->assertFalse($driver->can('delete', $log));
    }

    public function test_admin_garasi_memiliki_crud_penuh_pada_data_operasional(): void
    {
        $admin = $this->buatUser(User::ROLE_ADMIN_GARASI);
        $armada = $this->buatArmada();
        $log = $this->buatLogBbm($armada, $this->buatUser());
        $servis = $this->buatLogServis($armada);

        foreach ([$armada, $log, $servis] as $record) {
            $this->assertTrue($admin->can('viewAny', $record::class));
            $this->assertTrue($admin->can('create', $record::class));
            $this->assertTrue($admin->can('update', $record));
            $this->assertTrue($admin->can('delete', $record));
            $this->assertTrue($admin->can('restore', $record));
            $this->assertFalse($admin->can('forceDelete', $record));
        }
    }

    public function test_ketua_garasi_hanya_membaca_data_operasional(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $armada = $this->buatArmada();
        $log = $this->buatLogBbm($armada, $this->buatUser());
        $servis = $this->buatLogServis($armada);

        foreach ([$armada, $log, $servis] as $record) {
            $this->assertTrue($ketua->can('viewAny', $record::class));
            $this->assertFalse($ketua->can('create', $record::class));
            $this->assertFalse($ketua->can('update', $record));
            $this->assertFalse($ketua->can('delete', $record));
        }
    }

    public function test_admin_garasi_hanya_mengelola_akun_driver(): void
    {
        $admin = $this->buatUser(User::ROLE_ADMIN_GARASI);

        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue($admin->can('update', $this->buatUser(User::ROLE_DRIVER)));
        $this->assertFalse($admin->can('update', $this->buatUser(User::ROLE_KETUA_GARASI)));
        $this->assertFalse($admin->can('update', $this->buatUser(User::ROLE_ADMIN_GARASI)));
    }

    public function test_ketua_mengelola_semua_akun_namun_tidak_menghapus_diri_sendiri(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $admin = $this->buatUser(User::ROLE_ADMIN_GARASI);
        $driverBaru = $this->buatUser();

        $this->assertTrue($ketua->can('update', $admin));
        $this->assertFalse($ketua->can('delete', $ketua));
        $this->assertTrue($ketua->can('delete', $driverBaru));
    }

    public function test_akun_dengan_riwayat_bbm_tidak_dapat_dihapus(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $driver = $this->buatUser();
        $this->buatLogBbm($this->buatArmada(), $driver);

        $this->assertFalse($ketua->can('delete', $driver));
    }

    public function test_akun_dengan_log_bbm_yang_sudah_di_soft_delete_tetap_tidak_dapat_dihapus(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $driver = $this->buatUser();
        $log = $this->buatLogBbm($this->buatArmada(), $driver);

        $log->delete();

        $this->assertFalse($ketua->can('delete', $driver));
    }

    public function test_akun_nonaktif_tidak_dapat_masuk_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $aktif = $this->buatUser(User::ROLE_ADMIN_GARASI);
        $nonaktif = $this->buatUser(User::ROLE_ADMIN_GARASI, ['is_active' => false]);

        $this->assertTrue($aktif->canAccessPanel($panel));
        $this->assertFalse($nonaktif->canAccessPanel($panel));
    }
}
