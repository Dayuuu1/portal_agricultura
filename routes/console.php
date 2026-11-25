<?php

use App\Models\PageView;
use App\Models\SiteEvent;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Portal statistics older than PageView::RETENTION_MONTHS are deleted every night (needs the scheduler running in cron).
Schedule::command('model:prune', ['--model' => [PageView::class, SiteEvent::class]])->dailyAt('03:15');
