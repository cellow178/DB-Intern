<?php

namespace App\Jobs;

use App\Models\Presences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DoAttendanceProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $data = $this->data;

        $config = DB::selectOne("SELECT * FROM config_presence");
        // $rawPresences = DB::select("SELECT date, section_id, employee_id 
        //     FROM raw_presences 
        //     WHERE created_at >= ('" . $data["date"] . "'::date - INTERVAL '2 DAY') 
        //     AND employee_id=:employee_id AND section_id=:section_id
        //     GROUP BY date, section_id, employee_id", 
        //     ["employee_id" => $data["employee_id"], "section_id" => $data["section_id"]]);

        # CARI JADWAL DI JAM ABSEN
        $schedule = DB::selectOne("SELECT B.id, shift, B.date, B.date + B.start_hour AS start_schedule, 
        (CASE WHEN B.start_hour < B.end_hour THEN B.date ELSE (B.date + INTERVAL '1 DAY') END) + B.end_hour AS end_schedule 
            FROM shift_schedule_personel A
            LEFT JOIN shift_schedules B ON B.id=A.schedule_id
            WHERE A.employee_id=:employee_id AND B.section_id=:section_id AND B.date=:date", 
            ["employee_id" => $val->employee_id, "section_id" => $val->section_id, "date" => $val->date]);
        
        
        $rawIds = [];
        // Log::debug($val->date . " # " . $val->employee_id);
        $longShift = false;
        $checkIn = null;
        $latLongIn = null;
        $checkOut = null;
        $latLongOut = null;
        $isLate = false;
        $isPresent = false;
        $isAlpa = false;
        $isAbsent = false;
        $isOnLeave = false;
        $isOnTime = false;
        $isEarlyOut = false;
        $isOvertime = false;
        $lateDuration = "00:00:00";

        DB::statement("DELETE FROM attendance_recap WHERE employee_id=:employee_id AND section_id=:section_id AND date=:date", 
            ["employee_id" => $val->employee_id, "section_id" => $val->section_id, "date" => $val->date]);

        # CARI JADWAL HARI INI
        /*$schedules = DB::select("SELECT B.id, shift, B.date, B.date + B.start_hour AS start_schedule, 
        (CASE WHEN B.start_hour < B.end_hour THEN B.date ELSE (B.date + INTERVAL '1 DAY') END) + B.end_hour AS end_schedule 
            FROM shift_schedule_personel A
            LEFT JOIN shift_schedules B ON B.id=A.schedule_id
            WHERE A.employee_id=:employee_id AND B.section_id=:section_id AND B.date=:date", 
            ["employee_id" => $val->employee_id, "section_id" => $val->section_id, "date" => $val->date]);

        if ($schedules) {
            if (count($schedules) == 2) {
                $longShift = true;
            } 
        }*/

        if ($schedule) {
            $startHour = $schedule->start_schedule;
            $endHour = $schedule->end_schedule;

            # Menentukan absen masuk
            $findScanIn = DB::selectOne("SELECT * FROM raw_presences 
                WHERE section_id=:section_id AND employee_id=:employee_id AND status_code='scan_in' 
                AND scan_time>=('".$startHour."'::timestamp - '".$config->scan_in_tolerance."'::time)
                AND scan_time<'".$endHour."' ORDER BY scan_time ASC", 
                ["employee_id" => $val->employee_id, "section_id" => $val->section_id]);

            if ($findScanIn) {
                $rawIds[] = $findScanIn->id;
                $isPresent = true;
                $checkIn = $findScanIn->scan_time;
                $latLongIn = json_encode(["latitude" => $findScanIn->latitude, "longitude" => $findScanIn->longitude]);

                # apakah terlambat
                $calIn = DB::selectOne("SELECT '".$checkIn."'::timestamp - '".$startHour."'::timestamp AS selisih");
                if ($calIn->selisih > $config->max_late) {
                    $isLate = true;
                    $isOnTime = false;
                    $lateDuration = $calIn->selisih;
                } else {
                    $isLate = false;
                    $isOnTime = true;
                    $lateDuration = "00:00:00";
                }
            } else {
                $isAlpa = true;
                $isOnTime = false;
            }

            # Menentukan absen pulang
            $findScanOut = DB::selectOne("SELECT * FROM raw_presences 
                WHERE section_id=:section_id AND employee_id=:employee_id AND status_code='scan_out' 
                AND scan_time>='".$startHour."'
                AND scan_time<=('".$endHour."'::timestamp + '$config->scan_out_tolerance'::time)
                ORDER BY scan_time ASC",
                ["employee_id" => $val->employee_id, "section_id" => $val->section_id]);

            if ($findScanOut) {
                $rawIds[] = $findScanOut->id;
                $isPresent = true;
                $isAlpa = false;
                $checkOut = $findScanOut->scan_time;
                $latLongOut = json_encode(["latitude" => $findScanOut->latitude, "longitude" => $findScanOut->longitude]);
            }

            if ((isset($checkIn) && !isset($checkOut)) || (!isset($checkIn) && isset($checkOut)) || (isset($checkIn) && isset($checkOut) && ($checkOut > $checkIn))) {
                $isPresenceExist = DB::selectOne("SELECT * FROM presences WHERE section_id=:section_id AND employee_id=:employee_id AND date=:date", ["employee_id" => $val->employee_id, "section_id" => $val->section_id, "date" => $val->date]);

                if (!is_null($checkIn) && !is_null($checkOut)) {
                    $duration = DB::selectOne("SELECT '".$checkOut."'::timestamp - '".$checkIn."'::timestamp AS durasi")->durasi;
                } else {
                    $duration = "00:00:00";
                }

                if ($isPresenceExist) {
                    # update
                    Presences::where('id', $isPresenceExist->id)
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
                } else {
                    # insert
                    Presences::insert([
                        "employee_id" => $val->employee_id,
                        "section_id" =>  $val->section_id,
                        "date" => $val->date,
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
                        "created_at" => now(),
                        "updated_at" => now(),
                        "created_by" => Auth::id(),
                        "updated_by" => Auth::id(),
                    ]);
                }
            } else {
                DB::statement("DELETE FROM presences WHERE employee_id=:employee_id AND section_id=:section_id AND date=:date", ["date" => $val->date, "section_id" => $val->section_id, "employee_id" => $val->employee_id]);
            }
        }
        // Log::debug("aaaaaa");

        //
        if (count($rawIds) > 0) {
            DB::statement("UPDATE raw_presences SET flag_processed=true WHERE id IN (" . implode(",", $rawIds) . ")");
        }
    }
}
