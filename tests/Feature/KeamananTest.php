<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\LogBbm;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class KeamananTest extends TestCase
{
    use RefreshDatabase, MembuatData;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_header_keamanan_terpasang_pada_halaman_login(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy');
    }

    public function test_password_lemah_ditolak_saat_membuat_pengguna(): void
    {
        $this->actingAs($this->buatUser(User::ROLE_KETUA_GARASI));

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Driver Baru',
                'email' => 'lemah@test.local',
                'role' => User::ROLE_DRIVER,
                'password' => 'abc12345', // tanpa huruf besar
                'passwordConfirmation' => 'abc12345',
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'lemah@test.local']);
    }

    public function test_password_kuat_diterima_saat_membuat_pengguna(): void
    {
        $this->actingAs($this->buatUser(User::ROLE_KETUA_GARASI));

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Driver Baru',
                'email' => 'kuat@test.local',
                'role' => User::ROLE_DRIVER,
                'password' => 'Rahasia123',
                'passwordConfirmation' => 'Rahasia123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['email' => 'kuat@test.local', 'role' => User::ROLE_DRIVER]);
    }

    public function test_perintah_buat_akun_membuat_pengguna_dengan_role_yang_dipilih(): void
    {
        $this->artisan('fleet:buat-akun', [
            '--nama' => 'Ketua Uji',
            '--email' => 'ketua-uji@test.local',
            '--role' => 'ketua_garasi',
        ])
            ->expectsQuestion('Password', 'Rahasia123')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'ketua-uji@test.local', 'role' => User::ROLE_KETUA_GARASI]);
    }

    public function test_perintah_buat_akun_menolak_role_tidak_valid(): void
    {
        $this->artisan('fleet:buat-akun', [
            '--nama' => 'X', '--email' => 'x@test.local', '--role' => 'superadmin',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'x@test.local']);
    }

    public function test_perintah_pindahkan_berkas_menyalin_ke_disk_privat(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('nota-bbm/lama.jpg', 'isi-berkas');

        $log = $this->buatLogBbm($this->buatArmada(), $this->buatUser(), ['foto_nota' => 'nota-bbm/lama.jpg']);
        $this->assertInstanceOf(LogBbm::class, $log);

        $this->artisan('berkas:pindahkan-ke-privat')->assertSuccessful();

        Storage::disk('local')->assertExists('nota-bbm/lama.jpg');
        Storage::disk('public')->assertExists('nota-bbm/lama.jpg'); // asli dibiarkan

        $this->artisan('berkas:pindahkan-ke-privat', ['--hapus-asli' => true])->assertSuccessful();

        Storage::disk('public')->assertMissing('nota-bbm/lama.jpg');
        Storage::disk('local')->assertExists('nota-bbm/lama.jpg');
    }
}
