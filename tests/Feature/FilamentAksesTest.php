<?php

namespace Tests\Feature;

use App\Filament\Resources\ArmadaResource;
use App\Filament\Resources\LogServisResource;
use App\Filament\Resources\LogBbmResource\Pages\ListLogBbms;
use App\Filament\Resources\LogServisResource\Pages\ListLogServises;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

/**
 * Uji tingkat halaman (Livewire + Filament): memastikan aturan akses benar-benar berlaku di UI.
 */
class FilamentAksesTest extends TestCase
{
    use RefreshDatabase, MembuatData;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_driver_hanya_melihat_log_bbm_miliknya_di_tabel(): void
    {
        $armada = $this->buatArmada();
        $driverA = $this->buatUser();
        $driverB = $this->buatUser();
        $logA = $this->buatLogBbm($armada, $driverA);
        $logB = $this->buatLogBbm($armada, $driverB);

        $this->actingAs($driverA);

        Livewire::test(ListLogBbms::class)
            ->assertCanSeeTableRecords([$logA])
            ->assertCanNotSeeTableRecords([$logB]);
    }

    public function test_driver_ditolak_membuka_halaman_armada_dan_log_servis(): void
    {
        $this->actingAs($this->buatUser());

        $this->get(ArmadaResource::getUrl('index'))->assertForbidden();
        $this->get(LogServisResource::getUrl('index'))->assertForbidden();
    }

    public function test_ketua_dapat_menyetujui_servis_pending(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $servis = $this->buatLogServis($this->buatArmada(), ['biaya' => 2500000]);

        $this->actingAs($ketua);

        Livewire::test(ListLogServises::class)->callTableAction('approve', $servis);

        $servis->refresh();
        $this->assertSame('Approved', $servis->status_approval);
        $this->assertSame($ketua->id, $servis->approved_by);
    }

    public function test_ketua_dapat_menolak_servis_pending(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $servis = $this->buatLogServis($this->buatArmada(), ['biaya' => 2500000]);

        $this->actingAs($ketua);

        Livewire::test(ListLogServises::class)->callTableAction('reject', $servis);

        $this->assertSame('Rejected', $servis->fresh()->status_approval);
    }

    public function test_admin_garasi_tidak_melihat_tombol_approve(): void
    {
        $servis = $this->buatLogServis($this->buatArmada(), ['biaya' => 2500000]);

        $this->actingAs($this->buatUser(User::ROLE_ADMIN_GARASI));

        Livewire::test(ListLogServises::class)->assertTableActionHidden('approve', $servis);
    }

    public function test_admin_garasi_tidak_dapat_membuat_akun_ketua(): void
    {
        $this->actingAs($this->buatUser(User::ROLE_ADMIN_GARASI));

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Penyusup',
                'email' => 'penyusup@test.local',
                'role' => User::ROLE_KETUA_GARASI,
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
            ])
            ->call('create');

        // Entah ditolak validasi atau dipaksa jadi driver, tidak boleh ada akun ketua baru.
        $this->assertFalse(
            User::where('email', 'penyusup@test.local')->where('role', User::ROLE_KETUA_GARASI)->exists()
        );
    }
}
