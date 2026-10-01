<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('events:archive-expired')->daily();

// Schedule pembersihan file di storage/app/tmp
Schedule::call(function () {
    $disk = Storage::disk('local');
    $tmpDirectory = 'tmp';

    if ($disk->exists($tmpDirectory)) {
        $files = $disk->files($tmpDirectory);
        $now = now();

        foreach ($files as $file) {
            if ($now->diffInHours($disk->lastModified($file)) >= 24) {
                $disk->delete($file);
            }
        }
    }
})->daily()->name('clean-tmp-files');
