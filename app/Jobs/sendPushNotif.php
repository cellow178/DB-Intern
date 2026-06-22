<?php

namespace App\Jobs;

use App\CoreService\CoreException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class sendPushNotif implements ShouldQueue
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
        $data = $this->data;

        /* pushNotif($data->title, $data->content, $data->user_receiver_ids); */

        $sql = "SELECT n.*, a.name as rel_activity_id, jp.name AS rel_job_position_id, 
                e.user_id, e.fullname AS rel_user_receiver_id, ts.section_name as rel_section_id,
                CASE WHEN a.form_group_code IS NULL THEN (CASE WHEN a.code IS NULL THEN n.module_name ELSE a.code END) ELSE a.form_group_code END AS form_group_code, CASE WHEN a.period_type_code='scheduled' THEN 'permanent' WHEN a.period_type_code='anytime' THEN 'optional' ELSE null END AS period, CASE WHEN n.user_receiver_id IS NOT NULL THEN e.fullname WHEN n.job_position_id IS NOT NULL THEN jp.name ELSE r.role_name END AS rel_role_id
                FROM notifications n
                LEFT JOIN m_activities a on a.id=n.activity_id
                LEFT JOIN job_positions jp on jp.id=n.job_position_id
                LEFT JOIN employees e on e.id=n.user_receiver_id
                LEFT JOIN toll_sections ts on ts.id=n.section_id 
                LEFT JOIN roles r on r.id=n.role_id
                WHERE n.id=?";
        $notif = DB::selectOne($sql, [$data["id"]]);
        if (!is_null($notif->json_data)) $notif->json_data = json_decode($notif->json_data);
        if (in_array($notif->module_name, ["incident_actions", "accident_reports"])) {
            $notif->module_name = "incidents";
            if (!is_null($notif->json_data)) {
                $notif->module_id = isset($notif->json_data->incident_id) ? $notif->json_data->incident_id : null;
            }
        }

        $userReceiverIds = [];
        if ($notif) {
            if ($notif->status_code == "unseen") {
                if (!is_null($notif->user_id)) $userReceiverIds[] = $notif->user_id;
                if (!is_null($notif->job_position_id)) {
                    $receiverJobPositionIds = DB::select("SELECT user_id FROM employees WHERE section_id=? AND job_position_id=? AND user_id IS NOT NULL", [$notif->section_id, $notif->job_position_id]);
                    if ($receiverJobPositionIds) $userReceiverIds = array_column($receiverJobPositionIds, "user_id"); 
                }
                if (!is_null($notif->role_id)) {
                    $receiverByRoleIds = DB::select("SELECT A.user_id 
                        FROM mapping_users_roles A
                        JOIN users B ON B.id=A.user_id
                        JOIN employees C ON C.user_id=B.id
                        WHERE C.section_id=? AND A.role_id=? AND (C.active=true OR C.resign_date IS NULL)", [$notif->section_id, $notif->role_id]);
                    if ($receiverByRoleIds) $userReceiverIds = array_column($receiverByRoleIds, "user_id"); 
                }
                $userReceiverIds = array_unique($userReceiverIds);
                
                if (count($userReceiverIds)>0) {
                    # ONE SIGNAL
                    if (env("PUSH_NOTIF") == "onesignal") {
                        pushNotif($notif->title, $notif->content, $notif, $userReceiverIds);

                    # FCM
                    } else if (env("PUSH_NOTIF") == "fcm") {
                        $userReceiverDevices = DB::select("SELECT device_token FROM user_devices WHERE
                                             userid IN (" . implode(", ", $userReceiverIds) . ") AND 
                                             active=true ORDER BY userid ASC");
                        if ($userReceiverDevices) {
                            $userReceiverTokenDevices = array_column($userReceiverDevices, "device_token");  
                            if ($userReceiverTokenDevices) $responses = fcmPushNotif($notif->title, $notif->content, (array)$notif, $userReceiverTokenDevices);
                        }
                        // Log::debug($responses ?? []);
                    }
                }
            }

        }
    }
}
