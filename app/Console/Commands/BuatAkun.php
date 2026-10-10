<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class BuatAkun extends Command
{
    protected $signature = 'fleet:buat-akun
                            {--nama= : Nama lengkap}
                            {--email= : Alamat email}
                            {--role=ketua_garasi : driver | admin_garasi | ketua_garasi}';

    protected $description = 'Membuat akun pengguna (untuk akun pertama di production tanpa seeder demo)';

    public function handle(): int
    {
        $role = $this->option('role');

        if (! in_array($role, [User::ROLE_DRIVER, User::ROLE_ADMIN_GARASI, User::ROLE_KETUA_GARASI], true)) {
            $this->error('Role tidak valid. Pilih: driver, admin_garasi, atau ketua_garasi.');

            return self::FAILURE;
        }

        $nama = $this->option('nama') ?: text('Nama lengkap', required: true);

        $email = $this->option('email') ?: text(
            'Email',
            required: true,
            validate: fn (string $v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Format email tidak valid.',
        );

        if (User::where('email', $email)->exists()) {
            $this->error("Email {$email} sudah terdaftar.");

            return self::FAILURE;
        }

        $kataSandi = password(
            'Password',
            required: true,
            validate: function (string $v) {
                $validator = Validator::make(['password' => $v], ['password' => [Password::default()]]);

                return $validator->fails() ? $validator->errors()->first('password') : null;
            },
        );

        User::create([
            'name' => $nama,
            'email' => $email,
            'password' => $kataSandi,
            'role' => $role,
        ]);

        $this->info("Akun {$role} untuk {$email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
