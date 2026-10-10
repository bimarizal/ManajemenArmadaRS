<?php

use Illuminate\Support\Facades\Schedule;

// Backup (spatie/laravel-backup) dan pembersihan jejak audit lama.
// Butuh cron/Task Scheduler yang menjalankan `php artisan schedule:run` tiap menit (lihat docs/DEPLOYMENT.md).
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');
Schedule::command('activitylog:clean')->dailyAt('02:30');
Schedule::command('backup:monitor')->dailyAt('03:00');
