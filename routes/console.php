<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('crm:sync-segments')->dailyAt('01:00');
Schedule::command('crm:generate-reminders')->dailyAt('01:15');
// Backup database otomatis hanya kalau dinyalakan (DB_BACKUP_SCHEDULE=true): di Docker, mysqldump biasanya tidak ada di container app
// (MySQL ada di container lain), jadi jangan gagal tiap malam. Perintah manualnya tetap ada: php artisan db:backup
if (config('services.db_backup.schedule')) {
    Schedule::command('db:backup')->dailyAt('02:00');
}
Schedule::command('photobox:pull-gallery')->everyMinute()->withoutOverlapping();
