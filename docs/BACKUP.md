# Backup & Restore

Memakai `spatie/laravel-backup` **versi 9** (versi 10 ke atas butuh PHP 8.4). Backup berisi dump database
dan berkas foto (nota, kwitansi, laporan masalah) dalam satu zip.

## 1. Pasang & publikasikan konfigurasi

```bash
composer require spatie/laravel-backup:"^9.0"
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider" --tag="backup-config"
```

## 2. Siapkan disk khusus backup

Tambahkan di `config/filesystems.php`, bagian `disks`:

```php
'backups' => [
    'driver' => 'local',
    'root' => storage_path('app/backups'),
    'throw' => true,
],
```

Disk terpisah ini mencegah arsip backup ikut ter-backup di backup berikutnya.

## 3. Sesuaikan `config/backup.php`

| Kunci | Nilai yang disarankan |
|---|---|
| `backup.name` | `env('APP_NAME', 'sim-fleet-rs')` |
| `backup.source.files.include` | `[storage_path('app/private'), base_path('.env')]` |
| `backup.source.files.exclude` | `[]` |
| `backup.source.databases` | `['mysql']` |
| `backup.destination.disks` | `['backups']` (tambahkan disk lain di luar server, lihat bagian 5) |
| `backup.password` | `env('BACKUP_ARCHIVE_PASSWORD')` |
| `backup.encryption` | `'default'` bila password diisi |
| `notifications.mail.to` | alamat email admin sistem |

## 4. Lokasi `mysqldump`

Di `config/database.php`, pada koneksi `mysql`, tambahkan:

```php
'dump' => [
    'dump_binary_path' => env('DB_DUMP_PATH'),
],
```

Isi `DB_DUMP_PATH` di `.env` dengan **folder** berisi `mysqldump`:
- Laragon (Windows): mis. `C:\laragon\bin\mysql\mysql-8.x.x-winx64\bin`
- Linux: `/usr/bin`

## 5. Simpan salinan di luar server (wajib untuk produksi)

Backup yang hanya ada di server yang sama akan ikut hilang jika server rusak.
Tambahkan disk kedua (S3/SFTP/Google Drive/disk eksternal), lalu masukkan ke `backup.destination.disks`.

## 6. Uji

```bash
php artisan backup:run
php artisan backup:list
```

Hasil ada di `storage/app/backups/<nama aplikasi>/`. Penjadwalan harian sudah ada di `routes/console.php`
(backup 01:30, pembersihan 01:00, monitor 03:00) dan butuh scheduler berjalan (lihat `docs/DEPLOYMENT.md`).

## 7. Prosedur restore (WAJIB dicoba, jangan hanya dipercaya)

Lakukan di server/komputer uji, bukan di production, minimal sekali per kuartal:

1. Ekstrak arsip zip terbaru (masukkan password bila dienkripsi).
2. Buat database kosong, lalu impor dump:
   ```bash
   mysql -u root -p sim_fleet_rs_restore < db-dumps/mysql-sim_fleet_rs.sql
   ```
3. Salin folder berkas hasil ekstrak kembali ke `storage/app/private/`.
4. Arahkan `.env` ke database restore, jalankan aplikasi, lalu cek: login, daftar Log BBM, foto nota terbuka.
5. Catat tanggal, siapa yang menguji, dan hasilnya.

## 8. Retensi jejak audit

Pembersihan `activitylog:clean` memakai `delete_records_older_than_days` di `config/activitylog.php`
(bawaan 365 hari). Sesuaikan dengan kebijakan retensi rumah sakit (mis. `1095` untuk 3 tahun).
