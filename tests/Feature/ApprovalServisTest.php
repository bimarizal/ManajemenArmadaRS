<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class ApprovalServisTest extends TestCase
{
    use RefreshDatabase, MembuatData;

    public function test_biaya_di_atas_1_juta_dipaksa_pending(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);

        $servis = $this->buatLogServis($this->buatArmada(), [
            'biaya' => 1000001,
            'status_approval' => 'Approved',
            'approved_by' => $ketua->id,
        ])->fresh();

        $this->assertSame('Pending', $servis->status_approval);
        $this->assertNull($servis->approved_by);
    }

    public function test_biaya_tepat_1_juta_tidak_dipaksa_pending(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);

        $servis = $this->buatLogServis($this->buatArmada(), [
            'biaya' => 1000000,
            'status_approval' => 'Approved',
            'approved_by' => $ketua->id,
        ])->fresh();

        $this->assertSame('Approved', $servis->status_approval);
    }

    public function test_status_bawaan_adalah_pending(): void
    {
        $servis = $this->buatLogServis($this->buatArmada(), ['biaya' => 300000])->fresh();

        $this->assertSame('Pending', $servis->status_approval);
    }

    public function test_menaikkan_biaya_di_atas_1_juta_mengembalikan_status_ke_pending(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $servis = $this->buatLogServis($this->buatArmada(), [
            'biaya' => 900000, 'status_approval' => 'Approved', 'approved_by' => $ketua->id,
        ]);

        $servis->update(['biaya' => 2000000]);

        $servis->refresh();
        $this->assertSame('Pending', $servis->status_approval);
        $this->assertNull($servis->approved_by);
    }

    public function test_keputusan_ketua_tidak_dibatalkan_oleh_aturan_biaya(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $servis = $this->buatLogServis($this->buatArmada(), ['biaya' => 2000000]);
        $this->assertSame('Pending', $servis->fresh()->status_approval);

        $servis->update(['status_approval' => 'Approved', 'approved_by' => $ketua->id]);

        $servis->refresh();
        $this->assertSame('Approved', $servis->status_approval);
        $this->assertSame($ketua->id, $servis->approved_by);
    }

    public function test_mengubah_kolom_lain_tidak_mengubah_status_yang_sudah_disetujui(): void
    {
        $ketua = $this->buatUser(User::ROLE_KETUA_GARASI);
        $servis = $this->buatLogServis($this->buatArmada(), ['biaya' => 2000000]);
        $servis->update(['status_approval' => 'Approved', 'approved_by' => $ketua->id]);

        $servis->update(['deskripsi' => 'Catatan tambahan']);

        $this->assertSame('Approved', $servis->fresh()->status_approval);
    }
}
