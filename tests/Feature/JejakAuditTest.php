<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogResource;
use App\Models\Armada;
use App\Models\LogServis;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class JejakAuditTest extends TestCase
{
    use RefreshDatabase, MembuatData;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_pembuatan_armada_tercatat_beserta_pelakunya(): void
    {
        $admin = $this->buatUser(User::ROLE_ADMIN_GARASI);
        $this->actingAs($admin);

        $armada = $this->buatArmada();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Armada::class,
            'subject_id' => $armada->id,
            'event' => 'created',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_keputusan_approval_tercatat_dengan_nilai_sebelum_dan_sesudah(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $servis = $this->buatLogServis($this->buatArmada(), ['biaya' => 2000000]);

        $this->actingAs($ketua);
        $servis->update(['status_approval' => 'Approved', 'approved_by' => $ketua->id]);

        $log = Activity::where('subject_type', LogServis::class)
            ->where('subject_id', $servis->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($ketua->id, $log->causer_id);
        $this->assertSame('Pending', $log->properties['old']['status_approval']);
        $this->assertSame('Approved', $log->properties['attributes']['status_approval']);
    }

    public function test_hapus_dan_pulihkan_tercatat(): void
    {
        $armada = $this->buatArmada();

        $armada->delete();
        $armada->restore();

        $events = Activity::where('subject_type', Armada::class)->where('subject_id', $armada->id)->pluck('event');
        $this->assertTrue($events->contains('deleted'));
        $this->assertTrue($events->contains('restored'));
    }

    public function test_password_tidak_pernah_masuk_jejak_audit(): void
    {
        $user = $this->buatUser();
        $sebelum = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->count();

        $user->update(['password' => 'RahasiaBaru123']);

        $this->assertSame(
            $sebelum,
            Activity::where('subject_type', User::class)->where('subject_id', $user->id)->count(),
            'Mengubah password saja tidak boleh menghasilkan entri audit.'
        );
        $this->assertStringNotContainsString('password', json_encode(Activity::all()->pluck('properties')));
    }

    public function test_login_berhasil_tercatat(): void
    {
        $user = $this->buatUser(User::ROLE_ADMIN_GARASI);

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'event' => 'login',
            'causer_id' => $user->id,
        ]);
    }

    // Satu pengguna per tes: AuthenticateSession menyimpan hash password di sesi,
    // sehingga berganti pengguna di tengah tes membuat sesi dianggap tidak valid (302).
    public function test_ketua_boleh_membuka_jejak_audit(): void
    {
        $this->actingAs($this->buatUser(User::ROLE_KETUA_GARASI));

        $this->get(ActivityLogResource::getUrl('index'))->assertOk();
    }

    public function test_admin_garasi_ditolak_membuka_jejak_audit(): void
    {
        $this->actingAs($this->buatUser(User::ROLE_ADMIN_GARASI));

        $this->get(ActivityLogResource::getUrl('index'))->assertForbidden();
    }

    public function test_driver_ditolak_membuka_jejak_audit(): void
    {
        $this->actingAs($this->buatUser(User::ROLE_DRIVER));

        $this->get(ActivityLogResource::getUrl('index'))->assertForbidden();
    }
}
