<?php

namespace App\Console\Commands;

use App\Models\Events;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ArchiveExpiredEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:archive-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengarsipkan event yang sudah lewat H+7 hari dari end_date';

    public function handle()
    {
        $cutoffDate = Carbon::now()->subDays(3)->toDateString();

        $affected = Events::where('status', 'publish')
            ->whereDate('end_date', '<', $cutoffDate)
            ->update(['status' => 'archive']);

        $this->info("Berhasil mengarsipkan {$affected} event.");
    }
}
