# Checklist Deployment Production

## Server
- Linux (Ubuntu LTS), Nginx + PHP-FPM (PHP 8.3+), MySQL/MariaDB.
- Ekstensi PHP: `gd`, `intl`, `zip`, `mbstring`, `pdo_mysql`, `fileinfo`, `curl`, `opcache`.
- HTTPS (Let's Encrypt atau sertifikat instansi). `APP_URL` wajib `https://...`.
- User database khusus aplikasi (bukan `root`) dengan hak hanya pada database aplikasi.

## Nginx (inti)
```nginx
server {
    server_name fleet.contoh-rs.co.id;
    root /var/www/sim-fleet-rs/public;
    index index.php;
    client_max_body_size 10M;          # foto nota

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```

## Deploy / update
```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan filament:optimize        # Filament 3.3; jika tidak ada: filament:cache-components + icons:cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
Setelah mengubah `.env` di production, jalankan ulang `php artisan config:cache`.

## Scheduler (untuk backup & pembersihan)
Linux (crontab user web):
```
* * * * * cd /var/www/sim-fleet-rs && php artisan schedule:run >> /dev/null 2>&1
```
Windows: buat tugas di Task Scheduler yang menjalankan `php artisan schedule:run` setiap menit.

## Akun pertama (tanpa seeder demo)
Seeder menolak berjalan di production. Buat akun lewat:
```bash
php artisan fleet:buat-akun --role=ketua_garasi
php artisan fleet:buat-akun --role=admin_garasi
```
Akun lain dibuat dari menu **Pengguna** di aplikasi.

## Memindahkan foto lama ke penyimpanan privat
Jika sebelumnya foto tersimpan di disk publik:
```bash
php artisan berkas:pindahkan-ke-privat
php artisan berkas:pindahkan-ke-privat --hapus-asli   # setelah yakin semua foto terbuka
```

## Checklist sebelum go-live
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] HTTPS aktif, `SESSION_SECURE_COOKIE=true`
- [ ] Tidak ada akun demo (`*@simfleet.test`) di database
- [ ] SMTP diuji (reset password terkirim)
- [ ] `php artisan backup:run` berhasil, salinan di luar server ada, **restore sudah diuji**
- [ ] Scheduler berjalan
- [ ] `php artisan test` hijau di lingkungan staging
- [ ] Foto nota tidak bisa dibuka tanpa login (coba URL langsung di jendela incognito)
