# SIM-Fleet RS — Laravel 12 + FilamentPHP v3

File di folder ini adalah *overlay*: salin ke project Laravel 12 baru.

## Persyaratan

- PHP 8.2+ (ekstensi `gd`, `zip`, `intl` aktif)
- Composer 2
- Laravel 12.x
- **Filament v3.3 (`^3.3`)** — jangan pasang tanpa versi, karena akan menarik Filament generasi baru
  (v4/v5) yang API-nya berbeda dan tidak kompatibel dengan kode ini.

## Instalasi

```bash
composer create-project laravel/laravel sim-fleet-rs "12.*"
cd sim-fleet-rs
composer require filament/filament:"^3.3" maatwebsite/excel barryvdh/laravel-dompdf
php artisan filament:install --panels      # id panel: admin
# Salin isi folder overlay ini (app/, database/, resources/, routes/) ke project, timpa file yang sama.
php artisan storage:link
php artisan migrate:fresh --seed
php artisan serve
```

> Sudah pernah menginstal versi sebelumnya? Salin file terbaru lalu cukup jalankan `php artisan migrate`
> (menambah kolom `deleted_at` dan `users.is_active`), tanpa `migrate:fresh`.

> Jika memakai `laravel new` (installer), pilih **tanpa starter kit** agar tidak bentrok
> dengan login dan tabel `users` milik Filament.

### Konfigurasi

Pada `.env`:

```
APP_LOCALE=id
```

Zona waktu: ubah langsung di `config/app.php` (nilai `timezone` di skeleton Laravel
bersifat tetap, bukan dari `.env`):

```php
'timezone' => 'Asia/Jakarta',
```

Pastikan `bootstrap/providers.php` memuat `App\Providers\Filament\AdminPanelProvider::class`
(otomatis jika memakai `filament:install`).

## Akun demo (password: `password`)

| Role | Email |
|---|---|
| Ketua Garasi | ketua@simfleet.test |
| Admin Garasi | admin@simfleet.test |
| Driver | driver1@simfleet.test, driver2@simfleet.test |

Login di `/admin`.

## Kompatibilitas Laravel 12

Tidak ada perubahan kode dari versi Laravel 11: struktur `bootstrap/providers.php`,
`casts()` pada model, dan middleware panel Filament identik. Kode tidak memakai API yang
berubah di Laravel 12 / Carbon 3 (mis. `diffInDays`) maupun trait `HasUuids`.

## Fitur tambahan (rilis ini)

- **Manajemen Pengguna** (menu Master Data → Pengguna): tambah/ubah akun, atur role, nonaktifkan akun,
  ganti password. Ketua Garasi mengelola semua akun; Admin Garasi hanya akun Driver.
  Akun sendiri tidak bisa dinonaktifkan/diubah role-nya; akun yang punya riwayat BBM tidak bisa dihapus (nonaktifkan saja).
- **Ganti password sendiri:** menu profil di pojok kanan atas.
- **Soft delete:** Armada, Log BBM, dan Log Servis yang dihapus tidak hilang permanen.
  Admin Garasi bisa melihatnya lewat filter "Data terhapus" dan memulihkannya. Hapus permanen tidak diizinkan.
  Plat nomor armada yang dihapus tetap tampil pada riwayatnya, dan plat nomornya tetap "terpakai" (unik) sampai dipulihkan.
- **Tes otomatis** (di folder `tests/`): perhitungan BBM, aturan approval, traffic light dokumen, hak akses per role, soft delete,
  dan beberapa uji halaman Filament.

## Fase 1 (kesiapan perusahaan) — langkah pemasangan

Urutan penting: **pasang paket dulu, baru salin file project**.

```bash
# PHP 8.3 → pin versi (v5 activitylog dan v10 backup butuh PHP 8.4)
composer require spatie/laravel-activitylog:"^4.10" spatie/laravel-backup:"^9.0"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider" --tag="backup-config"
# salin file project terbaru (app, database, resources, routes, tests, docs)
php artisan migrate
php artisan berkas:pindahkan-ke-privat          # pindahkan foto lama ke penyimpanan privat
php artisan optimize:clear
php artisan test
```

Isi fase 1:
- **Jejak audit** (menu Sistem → Jejak Audit, khusus Ketua): siapa membuat/mengubah/menghapus/memulihkan data,
  nilai sebelum & sesudah, serta login berhasil/gagal. Password tidak pernah dicatat.
- **Foto privat:** nota, kwitansi, dan foto laporan disimpan di `storage/app/private` dan dibuka lewat URL sementara bertanda tangan
  (`APP_URL` harus sama dengan alamat yang dibuka di browser).
- **Backup & restore:** lihat `docs/BACKUP.md` (butuh sedikit konfigurasi manual di `config/backup.php`).
- **Keamanan akun:** password kuat, reset password via email (butuh SMTP), halaman profil, header keamanan,
  batas percobaan login (bawaan Filament), perintah `php artisan fleet:buat-akun` untuk akun pertama di production.
- **Production:** lihat `docs/DEPLOYMENT.md` dan `.env.production.example`.

## Menjalankan tes

Perlu ekstensi PHP `pdo_sqlite` (cek `php -m`). Pastikan `phpunit.xml` memuat
`DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:` (bawaan Laravel 12), lalu:

```bash
php artisan test
```

## Catatan desain

- Role memakai kolom `users.role` + Laravel Policies (tanpa Spatie), sesuai skema pada spesifikasi.
- Tabel tambahan `laporan_masalahs` untuk fitur "Laporkan Kendaraan Bermasalah" milik Driver.
- Servis dengan biaya <= Rp 1.000.000 tetap berstatus `Pending` (default skema) sampai Ketua memutuskan;
  biaya > Rp 1.000.000 dipaksa `Pending` oleh model event.
- Ekspor Excel/PDF (BBM + servis Approved) ada di halaman Log Servis, khusus Ketua Garasi.
