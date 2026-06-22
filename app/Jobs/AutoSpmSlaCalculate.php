<?php

namespace App\Jobs;

use App\Models\Incidents;
use App\Models\MonitoringTransactions;
use App\Models\SpmSla;
use App\Models\TollGates;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoSpmSlaCalculate implements ShouldQueue
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
        // Log::debug($data);
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

        $id = $data['module_id'];
        $moduleName = isset($data['module_name']) ? $data['module_name'] : null;
        $spmCode = isset($data['spm_code']) ? $data['spm_code'] : null;

        /* ==============================================================
        |       KECEPATAN TRANSAKSI DAN JUMLAH ANTRIAN
        ============================================================== */
        if ($moduleName == 'monitoring_transactions') {
            // TRANSAKSI
            $transaction = DB::selectOne("SELECT A.*, B.gate_name, C.shift FROM monitoring_transactions A
                LEFT JOIN toll_gates B ON B.id=A.gate_id
                LEFT JOIN shift_schedules C ON C.id=A.schedule_id
                WHERE A.id=:id", ["id" => $id]
            );

            $dataTempA = [];
            $items = DB::select("SELECT * FROM monitoring_transaction_details WHERE transaction_id=:transaction_id ORDER BY type, booth_number ASC, sample_number ASC", ["transaction_id" => $id]);

            foreach ($items as $item) {
                $dataTempA[$item->type][$item->booth_number][] = [
                    "sample_number" => $item->sample_number,
                    "vehicle_queue" => $item->vehicle_queue,
                    "transaction_duration" => (float)$item->transaction_duration,
                    "vehicle_type" => $item->vehicle_type,
                    "vehicle_class_id" => $item->vehicle_class_id,
                    "payment_id" => $item->payment_id,
                    "description" => $item->description,
                ];
            }

            $data_booths = [];
            foreach (["booth"] as $item) {
                $varBooth = "total_" . $item;
                if ($transaction->$varBooth > 0) {
                    for ($x = 1; $x <= $transaction->$varBooth; $x++) {
                        // 

                        if (isset($dataTempA[$item][$x])) {
                            $totalDuration = 0;
                            $totalQueue = 0;
                            $avgDuration = 0;
                            foreach ($dataTempA[$item][$x] as $sample) {
                                $transactionDuration = (float)($sample['transaction_duration']);
                                $totalDuration += $transactionDuration;
                                
                                $transactionQueue = (float)($sample['vehicle_queue']);
                                $totalQueue += $transactionQueue;
                            }

                            $data_booths[] = [
                                "type" => $item,
                                "booth_number" => $x,
                                "samples" => $dataTempA[$item][$x],
                                "total_transaction_duration" => (float)$totalDuration,
                                "average_transaction_duration" => (float)$totalDuration/count($dataTempA[$item][$x]),
                                "total_vehicle_queue" => (float)$totalQueue,
                                "average_vehicle_queue" => (float)$totalQueue/count($dataTempA[$item][$x])
                            ];
                        }
                    }
                }
            }

            # 1 Kecepatan transaksi rata-rata
            $spmCodes = ["kecepatan-transaksi-1", "kecepatan-transaksi-2", "kecepatan-transaksi-3", "kecepatan-transaksi-4", "kecepatan-transaksi-5"];
            $spmList = DB::select("SELECT * FROM view_section_spm_sla WHERE code IN ('".implode("','", $spmCodes)."') AND section_id=:section_id", ["section_id" => $transaction->section_id]);
            
            $dataSpmSla = [];
            foreach ($spmList as $spm) {
                foreach ($data_booths as $booth) {
                    $is_spm_passed = $booth['average_transaction_duration'] <= $spm->spm_parameter ? true : false;

                    // SLA
                    if ($spm->is_applicable == true) {
                        $is_sla_passed = $booth['average_transaction_duration'] <= $spm->sla_parameter ? true : false;
                    } else {
                        $is_sla_passed = null;
                    }

                    $description = "Rata-rata Gerbang " . $transaction->gate_name . " " . ($booth['type'] == "longbooth" ? "Longbooth " : "Gardu ");
                    $description .= " shift " . $transaction->shift;

                    $dataSpmSla[] = [
                        "section_id" => $transaction->section_id,
                        "spm_id" => $spm->spm_id,
                        "date" => $transaction->date,
                        "description" => $description,
                        "spm_specification" => $spm->spm_specification,
                        "sla_specification" => $spm->is_applicable ? $spm->sla_specification : null,
                        "operator" => $spm->operator,
                        "spm_parameter" => $spm->spm_parameter,
                        "sla_parameter" => $spm->is_applicable ? $spm->sla_parameter : null,
                        "unit" => $spm->unit,
                        "score" => $booth['average_transaction_duration'],
                        "is_applicable" => $spm->is_applicable,
                        "is_spm_passed" => $is_spm_passed,
                        "is_sla_passed" => $spm->is_applicable ? $is_sla_passed : null,
                        "activity_id" => null,
                        "module_name" => "monitoring_transactions",
                        "module_id" => $id,
                        "json_data" => json_encode(["gate_id" => $transaction->gate_id, "type" => $booth['type'], "booth_number" => $booth['booth_number'], "total_transaction_duration" => $booth['total_transaction_duration'], "average_transaction_duration" => $booth['average_transaction_duration']], true),
                        "created_by" => Auth::id(),
                        "updated_by" => Auth::id(),
                        "created_at" => Carbon::now(),
                        "updated_at" => Carbon::now(),
                    ];
                }
            }

            # 2 Jumlah antrian kendaraan
            $spmCodes = ["jumlah-antrian-kendaraan"];
            $spmList = DB::select("SELECT * FROM view_section_spm_sla WHERE code IN ('".implode("','", $spmCodes)."') AND section_id=:section_id", ["section_id" => $transaction->section_id]);

            foreach ($spmList as $spm) {
                foreach ($data_booths as $booth) {
                    $is_spm_passed = $booth['average_vehicle_queue'] <= $spm->spm_parameter ? true : false;

                    // SLA
                    if ($spm->is_applicable == true) {
                        $is_sla_passed = $booth['average_vehicle_queue'] <= $spm->sla_parameter ? true : false;
                    } else {
                        $is_sla_passed = null;
                    }

                    $description = "Rata-rata Gerbang " . $transaction->gate_name . " " . ($booth['type'] == "longbooth" ? "Longbooth " : "Gardu ");
                    $description .= " shift " . $transaction->shift;

                    $dataSpmSla[] = [
                        "section_id" => $transaction->section_id,
                        "spm_id" => $spm->spm_id,
                        "date" => $transaction->date,
                        "description" => $description,
                        "spm_specification" => $spm->spm_specification,
                        "sla_specification" => $spm->is_applicable ? $spm->sla_specification : null,
                        "operator" => $spm->operator,
                        "spm_parameter" => $spm->spm_parameter,
                        "sla_parameter" => $spm->is_applicable ? $spm->sla_parameter : null,
                        "unit" => $spm->unit,
                        "score" => $booth['average_vehicle_queue'],
                        "is_applicable" => $spm->is_applicable,
                        "is_spm_passed" => $is_spm_passed,
                        "is_sla_passed" => $spm->is_applicable ? $is_sla_passed : null,
                        "activity_id" => null,
                        "module_name" => "monitoring_transactions",
                        "module_id" => $id,
                        "json_data" => json_encode(["gate_id" => $transaction->gate_id, "type" => $booth['type'], "booth_number" => $booth['booth_number'], "total_vehicle_queue" => $booth['total_vehicle_queue'], "average_vehicle_queue" => $booth['average_vehicle_queue']], true),
                        "created_by" => Auth::id(),
                        "updated_by" => Auth::id(),
                        "created_at" => Carbon::now(),
                        "updated_at" => Carbon::now(),
                    ];
                }
            }

            # INSERT SPM SLA
            SpmSla::insert($dataSpmSla);
        }

        /* ==============================================================
        |                    PROGRES INSIDEN ACT 0-50
        | insiden-patroli-progres-0-50
        ============================================================== */
        $spm050List = [
            'insiden-patroli-progres-0-50', 
            'kecelakaan-penanganan-derek-0-50'
        ];
        if (in_array($spmCode, $spm050List)) {
            $stage = $data['stage'] ?? null;

            $incident = DB::selectOne("SELECT A.*, B.section_id, B.accident_date, B.is_accident, B.incident_name
                FROM incident_actions A
                JOIN incidents B ON B.id=A.incident_id
                WHERE A.id=:id", ["id" => $id]);
                
            $spm = DB::selectOne("SELECT * FROM view_section_spm_sla WHERE code=:spm_code AND section_id=:section_id", ["section_id" => $incident->section_id, "spm_code" => $spmCode]);

            $incidentActTime = DB::selectOne("
                WITH data AS (SELECT
                    (SELECT progress_at FROM incident_action_progress WHERE incident_action_id=:id AND stage=0) AS start_time,
                    (SELECT progress_at FROM incident_action_progress WHERE incident_action_id=:id AND stage=50) AS end_time)
                SELECT start_time, end_time, EXTRACT(EPOCH FROM (end_time-start_time))/60 AS selisih FROM data
            ", ["id" => $id]);

            if ($stage == 0) {
                SpmSla::insert([
                    "section_id" => $incident->section_id,
                    "spm_id" => $spm->spm_id,
                    "date" => $incident->accident_date,
                    "description" => "Insiden " . $incident->incident_name,
                    "spm_specification" => $spm->spm_specification,
                    "sla_specification" => $spm->is_applicable ? $spm->sla_specification : null,
                    "operator" => $spm->operator,
                    "spm_parameter" => $spm->spm_parameter,
                    "sla_parameter" => $spm->is_applicable ? $spm->sla_parameter : null,
                    "unit" => $spm->unit,
                    "score" => null,
                    "is_applicable" => $spm->is_applicable,
                    "is_spm_passed" => false,
                    "is_sla_passed" => $spm->is_applicable ? false : null,
                    "activity_id" => null,
                    "module_name" => "incident_actions",
                    "module_id" => $id,
                    "json_data" => json_encode(["incident_id" => $incident->incident_id, "is_accident" => $incident->is_accident, "0%" => $incidentActTime->start_time, "50%" => $incidentActTime->end_time]),
                    "created_by" => Auth::id(),
                    "updated_by" => Auth::id(),
                    "created_at" => Carbon::now(),
                    "updated_at" => Carbon::now(),
                ]);
            } else if ($stage == 50) {
                $spmsla = DB::selectOne("SELECT * FROM spm_sla WHERE module_name='incident_actions' AND module_id=:id AND spm_id=:spm_id", ["id" => $id, "spm_id" => $spm->spm_id]);

                if ($spmsla) {
                    // SPM
                    $unit = $spmsla->unit;
                    if ($unit == "minute") {
                        $standarSpm = $spmsla->spm_parameter;
                    } else if ($unit == "hour") {
                        $standarSpm = $spmsla->spm_parameter*60;
                    } else if ($unit == "second") {
                        $standarSpm = $spmsla->spm_parameter/60;
                    } else {
                        $standarSpm = $spmsla->spm_parameter;
                    }
                    $is_spm_passed = $incidentActTime->selisih <= $standarSpm ? true : false;

                    // SLA
                    if ($spmsla->is_applicable == true) {
                        if ($unit == "minute") {
                            $standarSla = $spmsla->sla_parameter;
                        } else if ($unit == "hour") {
                            $standarSla = $spmsla->sla_parameter*60;
                        } else if ($unit == "second") {
                            $standarSla = $spmsla->sla_parameter/60;
                        } else {
                            $standarSla = $spmsla->sla_parameter;
                        }
                        $is_sla_passed = $incidentActTime->selisih <= $standarSla ? true : false;
                    } else {
                        $is_sla_passed = null;
                    }

                    // Hasil from minute => satuan unit
                    if ($unit == "minute") {
                        $score = $incidentActTime->selisih;
                    } else if ($unit == "hour") {
                        $score = $incidentActTime->selisih/60;
                    } else if ($unit == "second") {
                        $score = $incidentActTime->selisih*60;
                    } else {
                        $standarSla = $spmsla->sla_parameter;
                    }

                    $objSpmSla = SpmSla::find($spmsla->id);
                    $objSpmSla->date = $incident->accident_date;
                    $objSpmSla->score = round($score,2);
                    $objSpmSla->is_spm_passed = $is_spm_passed;
                    $objSpmSla->is_sla_passed = $is_sla_passed;
                    $objSpmSla->json_data = ["incident_id" => $incident->incident_id, "is_accident" => $incident->is_accident, "0%" => $incidentActTime->start_time, "50%" => $incidentActTime->end_time];
                    $objSpmSla->save();

                }
            }
        }

        /* ==============================================================
        |                    PROGRES INSIDEN ACT 0-100
        | 
        ============================================================== */
        $spm0100List = [
            'insiden-derek-progres-0-100-1', 
            'insiden-derek-progres-0-100-2',
            'insiden-patroli-progres-0-100',
            'kecelakaan-ambulance-progres-0-100',
            'kecelakaan-derek-progres-0-100'
        ];
        if (in_array($spmCode, $spm0100List)) {
            $stage = $data['stage'] ?? null;

            $incident = DB::selectOne("SELECT A.*, B.section_id, B.accident_date, B.is_accident, B.incident_name 
                FROM incident_actions A
                JOIN incidents B ON B.id=A.incident_id
                WHERE A.id=:id", ["id" => $id]);
                
            $spm = DB::selectOne("SELECT * FROM view_section_spm_sla WHERE code=:spm_code AND section_id=:section_id", ["section_id" => $incident->section_id, "spm_code" => $spmCode]);

            $incidentActTime = DB::selectOne("
                WITH data AS (SELECT
                    (SELECT progress_at FROM incident_action_progress WHERE incident_action_id=:id AND stage=0) AS start_time,
                    (SELECT progress_at FROM incident_action_progress WHERE incident_action_id=:id AND stage=100) AS end_time)
                SELECT start_time, end_time, EXTRACT(EPOCH FROM (end_time-start_time))/60 AS selisih FROM data
            ", ["id" => $id]);

            if ($stage == 0) {
                SpmSla::insert([
                    "section_id" => $incident->section_id,
                    "spm_id" => $spm->spm_id,
                    "date" => $incident->accident_date,
                    "description" => "Insiden " . $incident->incident_name,
                    "spm_specification" => $spm->spm_specification,
                    "sla_specification" => $spm->is_applicable ? $spm->sla_specification : null,
                    "operator" => $spm->operator,
                    "spm_parameter" => $spm->spm_parameter,
                    "sla_parameter" => $spm->is_applicable ? $spm->sla_parameter : null,
                    "unit" => $spm->unit,
                    "score" => null,
                    "is_applicable" => $spm->is_applicable,
                    "is_spm_passed" => false,
                    "is_sla_passed" => $spm->is_applicable ? false : null,
                    "activity_id" => null,
                    "module_name" => "incident_actions",
                    "module_id" => $id,
                    "json_data" => json_encode(["incident_id" => $incident->incident_id, "is_accident" => $incident->is_accident, "0%" => $incidentActTime->start_time, "100%" => $incidentActTime->end_time]),
                    "created_by" => Auth::id(),
                    "updated_by" => Auth::id(),
                    "created_at" => Carbon::now(),
                    "updated_at" => Carbon::now(),
                ]);
            } else if ($stage == 100) {
                $spmsla = DB::selectOne("SELECT * FROM spm_sla WHERE module_name='incident_actions' AND module_id=:id AND spm_id=:spm_id", ["id" => $id, "spm_id" => $spm->spm_id]);

                if ($spmsla) {
                    // SPM
                    $unit = $spmsla->unit;
                    if ($unit == "minute") {
                        $standarSpm = $spmsla->spm_parameter;
                    } else if ($unit == "hour") {
                        $standarSpm = $spmsla->spm_parameter*60;
                    } else if ($unit == "second") {
                        $standarSpm = $spmsla->spm_parameter/60;
                    } else {
                        $standarSpm = $spmsla->spm_parameter;
                    }
                    $is_spm_passed = $incidentActTime->selisih <= $standarSpm ? true : false;

                    // SLA
                    if ($spmsla->is_applicable == true) {
                        if ($unit == "minute") {
                            $standarSla = $spmsla->sla_parameter;
                        } else if ($unit == "hour") {
                            $standarSla = $spmsla->sla_parameter*60;
                        } else if ($unit == "second") {
                            $standarSla = $spmsla->sla_parameter/60;
                        } else {
                            $standarSla = $spmsla->sla_parameter;
                        }
                        $is_sla_passed = $incidentActTime->selisih <= $standarSla ? true : false;
                    } else {
                        $is_sla_passed = null;
                    }

                    // Hasil from minute => satuan unit
                    if ($unit == "minute") {
                        $score = $incidentActTime->selisih;
                    } else if ($unit == "hour") {
                        $score = $incidentActTime->selisih/60;
                    } else if ($unit == "second") {
                        $score = $incidentActTime->selisih*60;
                    } else {
                        $standarSla = $spmsla->sla_parameter;
                    }

                    $objSpmSla = SpmSla::find($spmsla->id);
                    $objSpmSla->date = $incident->accident_date;
                    $objSpmSla->score = round($score,2);
                    $objSpmSla->is_spm_passed = $is_spm_passed;
                    $objSpmSla->is_sla_passed = $is_sla_passed;
                    $objSpmSla->json_data = ["incident_id" => $incident->incident_id, "is_accident" => $incident->is_accident, "0%" => $incidentActTime->start_time, "100%" => $incidentActTime->end_time];
                    $objSpmSla->save();

                }
            }
        }

        /* ==============================================================
        |                    insiden-penanganan
        ============================================================== */
        if ($spmCode == 'insiden-penanganan') {
            $stage = $data['stage'] ?? null;

            $incident = Incidents::find($id);
            $incidentTime = $incident->accident_date . " " . $incident->accident_time;

            $spm = DB::selectOne("SELECT * FROM view_section_spm_sla WHERE code=:spm_code AND section_id=:section_id", ["section_id" => $incident->section_id, "spm_code" => $spmCode]);

            if ($stage == 'created') {
                SpmSla::insert([
                    "section_id" => $incident->section_id,
                    "spm_id" => $spm->spm_id,
                    "date" => $incident->accident_date,
                    "description" => "Insiden " . $incident->incident_name,
                    "spm_specification" => $spm->spm_specification,
                    "sla_specification" => $spm->is_applicable ? $spm->sla_specification : null,
                    "operator" => $spm->operator,
                    "spm_parameter" => $spm->spm_parameter,
                    "sla_parameter" => $spm->is_applicable ? $spm->sla_parameter : null,
                    "unit" => $spm->unit,
                    "score" => null,
                    "is_applicable" => $spm->is_applicable,
                    "is_spm_passed" => false,
                    "is_sla_passed" => $spm->is_applicable ? false : null,
                    "activity_id" => null,
                    "module_name" => "incidents",
                    "module_id" => $id,
                    "json_data" => json_encode(["is_accident" => $incident->is_accident, "accident_time" => $incidentTime, "0%" => null]),
                    "created_by" => Auth::id(),
                    "updated_by" => Auth::id(),
                    "created_at" => Carbon::now(),
                    "updated_at" => Carbon::now(),
                ]);

            } else if ($stage == 'first_act') {
                $spmsla = DB::selectOne("SELECT * FROM spm_sla WHERE module_name='incidents' AND module_id=:id AND spm_id=:spm_id", ["id" => $id, "spm_id" => $spm->spm_id]);

                if ($spmsla) {
                    $incidentActTime = DB::selectOne("WITH data as (SELECT
                        (SELECT CONCAT(accident_date, ' ', accident_time) FROM incidents 
                        WHERE id=:id) AS accident_time,
                        (SELECT MIN(TO_CHAR(A.progress_at, 'YYYY-MM-DD HH24:MI:SS')) FROM incident_action_progress A
                        JOIN incident_actions B ON B.id=A.incident_action_id
                        WHERE B.incident_id=:id AND A.stage=0) AS first_act_time
                    )
                    SELECT accident_time, first_act_time,
                    EXTRACT(EPOCH FROM (TO_TIMESTAMP(first_act_time , 'YYYY-MM-DD HH24:MI:SS')-TO_TIMESTAMP(accident_time, 'YYYY-MM-DD HH24:MI:SS')))/60 AS selisih FROM data", ["id" => $id]);

                    // SPM
                    $unit = $spmsla->unit;
                    if ($unit == "minute") {
                        $standarSpm = $spmsla->spm_parameter;
                    } else if ($unit == "hour") {
                        $standarSpm = $spmsla->spm_parameter*60;
                    } else if ($unit == "second") {
                        $standarSpm = $spmsla->spm_parameter/60;
                    } else {
                        $standarSpm = $spmsla->spm_parameter;
                    }
                    $is_spm_passed = $incidentActTime->selisih <= $standarSpm ? true : false;

                    // SLA
                    if ($spmsla->is_applicable == true) {
                        if ($unit == "minute") {
                            $standarSla = $spmsla->sla_parameter;
                        } else if ($unit == "hour") {
                            $standarSla = $spmsla->sla_parameter*60;
                        } else if ($unit == "second") {
                            $standarSla = $spmsla->sla_parameter/60;
                        } else {
                            $standarSla = $spmsla->sla_parameter;
                        }
                        $is_sla_passed = $incidentActTime->selisih <= $standarSla ? true : false;
                    } else {
                        $is_sla_passed = null;
                    }

                    // Hasil from minute => satuan unit
                    if ($unit == "minute") {
                        $score = $incidentActTime->selisih;
                    } else if ($unit == "hour") {
                        $score = $incidentActTime->selisih/60;
                    } else if ($unit == "second") {
                        $score = $incidentActTime->selisih*60;
                    } else {
                        $standarSla = $spmsla->sla_parameter;
                    }

                    $objSpmSla = SpmSla::find($spmsla->id);
                    $objSpmSla->date = $incident->accident_date;
                    $objSpmSla->score = round($score,2);
                    $objSpmSla->is_spm_passed = $is_spm_passed;
                    $objSpmSla->is_sla_passed = $is_sla_passed;
                    $objSpmSla->json_data = ["is_accident" => $incident->is_accident, "accident_time" => $incidentActTime->accident_time, "0%" => $incidentActTime->first_act_time];
                    $objSpmSla->save();

                }
            }
        }

        /* ==============================================================
        |                    kecelakaan-penanganan-derek
        ============================================================== */
        if ($spmCode == 'kecelakaan-penanganan-derek') {
            $stage = $data['stage'] ?? null;

            #
            $incident = DB::selectOne("SELECT A.*, B.section_id, B.accident_date, B.accident_time, B.is_accident, B.incident_name 
                FROM incident_actions A
                JOIN incidents B ON B.id=A.incident_id
                WHERE A.id=:id", ["id" => $id]);
                
            $spm = DB::selectOne("SELECT * FROM view_section_spm_sla WHERE code=:spm_code AND section_id=:section_id", ["section_id" => $incident->section_id, "spm_code" => $spmCode]);
            $incidentTime = $incident->accident_date . " " . $incident->accident_time;

            if ($stage == 'created') {
                SpmSla::insert([
                    "section_id" => $incident->section_id,
                    "spm_id" => $spm->spm_id,
                    "date" => $incident->accident_date,
                    "description" => "Insiden " . $incident->incident_name,
                    "spm_specification" => $spm->spm_specification,
                    "sla_specification" => $spm->is_applicable ? $spm->sla_specification : null,
                    "operator" => $spm->operator,
                    "spm_parameter" => $spm->spm_parameter,
                    "sla_parameter" => $spm->is_applicable ? $spm->sla_parameter : null,
                    "unit" => $spm->unit,
                    "score" => null,
                    "is_applicable" => $spm->is_applicable,
                    "is_spm_passed" => false,
                    "is_sla_passed" => $spm->is_applicable ? false : null,
                    "activity_id" => null,
                    "module_name" => "incident_actions",
                    "module_id" => $id,
                    "json_data" => json_encode(["is_accident" => $incident->is_accident, "accident_time" => $incidentTime, "0%" => null]),
                    "created_by" => Auth::id(),
                    "updated_by" => Auth::id(),
                    "created_at" => Carbon::now(),
                    "updated_at" => Carbon::now(),
                ]);

            } else if ($stage == 'first_act') {
                $spmsla = DB::selectOne("SELECT * FROM spm_sla WHERE module_name='incident_actions' AND module_id=:id AND spm_id=:spm_id", ["id" => $id, "spm_id" => $spm->spm_id]);

                if ($spmsla) {
                    $incidentActTime = DB::selectOne("WITH data as (SELECT
                        (SELECT CONCAT(accident_date, ' ', accident_time) FROM incidents A
                        JOIN incident_actions B ON B.incident_id=A.id
                        WHERE B.id=:id) AS accident_time,
                        (SELECT MIN(TO_CHAR(A.progress_at, 'YYYY-MM-DD HH24:MI:SS')) FROM incident_action_progress A
                        JOIN incident_actions B ON B.id=A.incident_action_id
                        WHERE B.id=:id AND A.stage=0) AS first_act_time
                    )
                    SELECT accident_time, first_act_time,
                    EXTRACT(EPOCH FROM (TO_TIMESTAMP(first_act_time , 'YYYY-MM-DD HH24:MI:SS')-TO_TIMESTAMP(accident_time, 'YYYY-MM-DD HH24:MI:SS')))/60 AS selisih FROM data", ["id" => $id]);

                    // SPM
                    $unit = $spmsla->unit;
                    if ($unit == "minute") {
                        $standarSpm = $spmsla->spm_parameter;
                    } else if ($unit == "hour") {
                        $standarSpm = $spmsla->spm_parameter*60;
                    } else if ($unit == "second") {
                        $standarSpm = $spmsla->spm_parameter/60;
                    } else {
                        $standarSpm = $spmsla->spm_parameter;
                    }
                    $is_spm_passed = $incidentActTime->selisih <= $standarSpm ? true : false;

                    // SLA
                    if ($spmsla->is_applicable == true) {
                        if ($unit == "minute") {
                            $standarSla = $spmsla->sla_parameter;
                        } else if ($unit == "hour") {
                            $standarSla = $spmsla->sla_parameter*60;
                        } else if ($unit == "second") {
                            $standarSla = $spmsla->sla_parameter/60;
                        } else {
                            $standarSla = $spmsla->sla_parameter;
                        }
                        
                        $is_sla_passed = $incidentActTime->selisih <= $standarSla ? true : false;
                    } else {
                        $is_sla_passed = null;
                    }

                    // Hasil from minute => satuan unit
                    if ($unit == "minute") {
                        $score = $incidentActTime->selisih;
                    } else if ($unit == "hour") {
                        $score = $incidentActTime->selisih/60;
                    } else if ($unit == "second") {
                        $score = $incidentActTime->selisih*60;
                    } else {
                        $standarSla = $spmsla->sla_parameter;
                    }

                    $objSpmSla = SpmSla::find($spmsla->id);
                    $objSpmSla->date = $incident->accident_date;
                    $objSpmSla->score = round($score,2);
                    $objSpmSla->is_spm_passed = $is_spm_passed;
                    $objSpmSla->is_sla_passed = $is_sla_passed;
                    $objSpmSla->json_data = ["is_accident" => $incident->is_accident, "accident_time" => $incidentActTime->accident_time, "0%" => $incidentActTime->first_act_time];
                    $objSpmSla->save();

                }
            }
        }
    }
}
