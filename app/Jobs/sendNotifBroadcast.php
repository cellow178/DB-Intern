<?php

namespace App\Jobs;

use App\CoreService\CoreException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class sendNotifBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $data = $this->data;
        $title = $data["title"];
        $content = $data["content"];
        $dataNotif = $data["data"];
        $userReceiverTokenDevices = $data["device_tokens"];

        fcmPushNotif($title, $content, (array)$dataNotif, $userReceiverTokenDevices);
    }
}
