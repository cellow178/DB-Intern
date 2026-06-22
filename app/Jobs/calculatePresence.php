<?php

namespace App\Jobs;

use App\Models\Presences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class calculatePresence implements ShouldQueue
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
        Log::channel('customlog')->debug('worker start');
        $data = $this->data;

        $employeeId = $data["employee_id"];
        $sectionId = $data["section_id"];
        $date = $data["date"];
        $schedule = $data["schedule"];
        $radiusPresence = $data["radius"];
        $input = $data["input"];
        $config = $data["config"];

        if ($input["status_code"] == "scan_in") {
            $checkIn = $input["scan_time"];
            $latLongIn = json_encode(["latitude" => $input["latitude"], "longitude" => $input["longitude"], "radius_name" => $radiusPresence->name, "radius_latitude" => $radiusPresence->latitude, "radius_longitude" => $radiusPresence->longitude]);
            $checkOut = $schedule->check_out;
            $latLongOut = $schedule->lat_long_out;
        } else {
            $checkIn = $schedule->check_in;
            $latLongIn = $schedule->lat_long_in;
            $checkOut = $input["scan_time"];
            $latLongOut = json_encode(["latitude" => $input["latitude"], "longitude" => $input["longitude"], "radius_name" => $radiusPresence->name, "radius_latitude" => $radiusPresence->latitude, "radius_longitude" => $radiusPresence->longitude]);
        }

        if (!is_null($checkIn) && !is_null($checkOut) && ($checkOut > $checkIn)) {
            $duration = DB::selectOne("SELECT (EXTRACT(DAY FROM ('".$checkOut."'::timestamp - '".$checkIn."'::timestamp)) * 24 + EXTRACT(HOUR FROM ('".$checkOut."'::timestamp - '".$checkIn."'::timestamp))) || ':' || TO_CHAR(EXTRACT(MINUTE FROM ('".$checkOut."'::timestamp - '".$checkIn."'::timestamp)), 'FM00') || ':' || TO_CHAR(EXTRACT(SECOND FROM ('".$checkOut."'::timestamp - '".$checkIn."'::timestamp)), 'FM00') AS durasi")->durasi;
        } else {
            $duration = "00:00:00";
        }

        Log::channel('customlog')->debug('kalkulasi absen');

        if (!is_null($checkIn)) {
            # apakah terlambat
            $calIn = DB::selectOne("SELECT '".$checkIn."'::timestamp - '".$schedule->start_schedule."'::timestamp AS selisih");
            if ($calIn->selisih > $config->max_late) {
                $isLate = true;
                $isOnTime = false;
                $lateDuration = $calIn->selisih;
            } else {
                $isLate = false;
                $isOnTime = true;
                $lateDuration = "00:00:00";
            }
        }
            

        if (is_null($schedule->presence_id)) {
            # insert
            Presences::insert([
                "employee_id" => $employeeId,
                "section_id" =>  $sectionId,
                "date" => $date,
                "schedule_id" => $schedule->id,
                "check_in" => $checkIn ?? null,
                "check_in_tz" => $checkIn ?? null,
                "lat_long_in" => $latLongIn ?? null,
                "check_out" => $checkOut ?? null,
                "check_out_tz" => $checkOut  ?? null,
                "lat_long_out" => $latLongOut ?? null,
                "duration" => $duration,
                "is_on_time" => $isOnTime,
                "late_duration" => $lateDuration,
                "created_at" => now(),
                "updated_at" => now(),
                "created_by" => Auth::id(),
                "updated_by" => Auth::id(),
            ]);
        } else {
            Presences::where('id', $schedule->presence_id)
                ->update([
                    "schedule_id" => $schedule->id,
                    "check_in" => $checkIn,
                    "check_in_tz" => $checkIn,
                    "lat_long_in" => $latLongIn,
                    "check_out" => $checkOut,
                    "check_out_tz" => $checkOut,
                    "lat_long_out" => $latLongOut,
                    "duration" => $duration,
                    "is_on_time" => $isOnTime,
                    "late_duration" => $lateDuration,
                    "updated_at" => now(),
                    "updated_by" => Auth::id(),
                ]);
        }

        Log::channel('customlog')->info('done absen');

        
        # CALC OVERTIME
        if (!is_null($checkIn) && !is_null($checkOut) && ($checkOut > $checkIn)) {
            $sql = "SELECT A.id, A.section_id, A.applicant_id, A.date, A.start_time, A.estimated_duration,
                    (CONCAT(A.date,' ', A.start_time)::TIMESTAMP + (A.estimated_duration || ' minutes')::INTERVAL) AS estimated_end,
                    B.id AS presence_id, B.check_in, B.check_out,
                    CASE WHEN CONCAT(A.date,' ', A.start_time)::TIMESTAMP>B.check_in 
                        THEN CONCAT(A.date,' ', A.start_time)::TIMESTAMP 
                        ELSE B.check_in
                    END AS realization_start_time,
                    CASE WHEN (CONCAT(A.date,' ', A.start_time)::TIMESTAMP + (A.estimated_duration || ' minutes')::INTERVAL)<B.check_out 
                        THEN (CONCAT(A.date,' ', A.start_time)::TIMESTAMP + (A.estimated_duration || ' minutes')::INTERVAL) 
                        ELSE B.check_out
                    END AS realization_end_time
                    FROM overtimes A
                    LEFT JOIN presences B ON B.employee_id=A.applicant_id 
                        AND (CONCAT(A.date,' ', A.start_time)::TIMESTAMP <= B.check_out AND (CONCAT(A.date,' ', A.start_time)::TIMESTAMP + (A.estimated_duration || ' minutes')::INTERVAL) >= B.check_in)
                    WHERE A.applicant_id=:applicant_id AND A.status_code='approved' 
                    AND CONCAT(A.date,' ', A.start_time)::TIMESTAMP <= :scan_out AND (CONCAT(A.date,' ', A.start_time)::TIMESTAMP + (A.estimated_duration || ' minutes')::INTERVAL) > :scan_in";
            $realizationOvertime = DB::selectOne($sql, ["applicant_id" => $employeeId, "scan_in" => $checkIn, "scan_out" => $checkOut]);
            if ($realizationOvertime) {
                if (!is_null($realizationOvertime->realization_start_time) && !is_null($realizationOvertime->realization_end_time) && ($realizationOvertime->realization_end_time>$realizationOvertime->realization_start_time)) {
                    $realizationDuration  = DB::selectOne("SELECT (EXTRACT(DAY FROM ('".$realizationOvertime->realization_end_time."'::timestamp - '".$realizationOvertime->realization_start_time."'::timestamp)) * 24 + EXTRACT(HOUR FROM ('".$realizationOvertime->realization_end_time."'::timestamp - '".$realizationOvertime->realization_start_time."'::timestamp))) || ':' || TO_CHAR(EXTRACT(MINUTE FROM ('".$realizationOvertime->realization_end_time."'::timestamp - '".$realizationOvertime->realization_start_time."'::timestamp)), 'FM00') || ':' || TO_CHAR(EXTRACT(SECOND FROM ('".$realizationOvertime->realization_end_time."'::timestamp - '".$realizationOvertime->realization_start_time."'::timestamp)), 'FM00') AS durasi")->durasi;

                    DB::table("overtimes")->where("id", $realizationOvertime->id)
                        ->update([
                            "realization_start_time" => $realizationOvertime->realization_start_time,
                            "realization_end_time" => $realizationOvertime->realization_end_time,
                            "overtime_duration" => $realizationDuration
                        ]);
                }
            }
        }


    }
}
