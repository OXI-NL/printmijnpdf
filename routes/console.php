<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Dagelijks oude PDF's opruimen (ouder dan 7 dagen)
// Vereist cronjob: * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
// Als je geen cronjob kunt instellen, draait deze cleanup niet automatisch.
Schedule::command('pdfs:cleanup --days=7')->daily()->at('03:00');
