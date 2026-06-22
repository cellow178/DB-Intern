<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class importDefaultPayroll implements ShouldQueue
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
        if (count($data["default_employee_salary"]) > 0) {
            $chunkAttachment =  array_chunk($data["default_employee_salary"], 20);
            foreach ($chunkAttachment as $key => $value) {
                DB::table("default_employee_salary")
                    ->upsert(
                        $value,
                        ["employee_id", "component_id"],
                        ["employee_id", "component_id", "amount", "updated_by", "updated_at"]
                    );
            }
        }
    }
}
