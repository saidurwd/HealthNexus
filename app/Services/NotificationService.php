<?php

namespace App\Services;

use App\Mail\SystemNotification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    protected array $templates = [
        'password_reset' => [
            'title' => 'Password Reset',
            'message' => 'You requested a password reset. Your reset token is :token.',
        ],
        'security_alert' => [
            'title' => 'Security Alert',
            'message' => 'A new sign-in was detected for your account on :device.',
        ],
        'appointment_reminder' => [
            'title' => 'Appointment Reminder',
            'message' => ':patient has an appointment scheduled on :date at :time.',
        ],
        'approval_required' => [
            'title' => 'Approval Required',
            'message' => 'An item requires your approval: :item.',
        ],
        'system_maintenance' => [
            'title' => 'System Maintenance',
            'message' => 'Scheduled maintenance at :time.',
        ],
        'sample_rejected' => [
            'title' => 'Lab Sample Rejected',
            'message' => 'Specimen :accession_number for :patient was rejected (:reason). Recollection is required.',
        ],
        'critical_result' => [
            'title' => 'Critical Lab Result',
            'message' => ':test for :patient is critical: :value. Immediate review required.',
        ],
        'report_ready' => [
            'title' => 'Lab Report Ready',
            'message' => 'Lab report :report_number for :patient is ready.',
        ],
        'report_amended' => [
            'title' => 'Lab Report Amended',
            'message' => 'Lab report :report_number for :patient has been amended. Reason: :reason.',
        ],
        'radiology_critical_finding' => [
            'title' => 'Critical Radiology Finding',
            'message' => 'Critical finding on :procedure for :patient: :finding. Immediate review required.',
        ],
        'radiology_report_ready' => [
            'title' => 'Radiology Report Ready',
            'message' => 'Radiology report :report_number for :patient is ready.',
        ],
        'radiology_report_amended' => [
            'title' => 'Radiology Report Amended',
            'message' => 'Radiology report :report_number for :patient has been amended. Reason: :reason.',
        ],
        'pharmacy_expiry_alert' => [
            'title' => 'Medication Nearing Expiry',
            'message' => 'Batch :batch_number of :medication (store: :store) expires on :expiry_date.',
        ],
        'pharmacy_low_stock' => [
            'title' => 'Low Pharmacy Stock',
            'message' => ':medication is below its reorder level at :store (:quantity remaining, reorder at :reorder_level).',
        ],
        'ipd_admission_approved' => [
            'title' => 'Admission Approved',
            'message' => 'Admission request for :patient has been approved.',
        ],
        'ipd_bed_allocated' => [
            'title' => 'Bed Allocated',
            'message' => ':patient has been allocated bed :bed_code (admission :admission_number).',
        ],
        'ipd_transfer_requested' => [
            'title' => 'Patient Transfer Requested',
            'message' => 'Transfer requested for :patient (admission :admission_number) to bed :bed_code.',
        ],
        'ipd_transfer_completed' => [
            'title' => 'Patient Transfer Completed',
            'message' => ':patient (admission :admission_number) has been transferred to bed :bed_code.',
        ],
        'ipd_discharge_requested' => [
            'title' => 'Discharge Requested',
            'message' => 'Discharge requested for :patient (admission :admission_number).',
        ],
        'ipd_discharge_completed' => [
            'title' => 'Discharge Completed',
            'message' => ':patient (admission :admission_number) has been discharged.',
        ],
        'ipd_delayed_discharge' => [
            'title' => 'Delayed Discharge',
            'message' => ':patient (admission :admission_number, :ward) is :days_overdue day(s) past the expected discharge date.',
        ],
        'ipd_bed_reservation_expiring' => [
            'title' => 'Bed Reservation Expiring',
            'message' => 'The reservation on bed :bed_code expires soon.',
        ],

        'nursing_patient_assigned' => [
            'title' => 'New Patient Assignment',
            'message' => 'You have been assigned to :patient (admission :admission_number).',
        ],
        'nursing_task_overdue' => [
            'title' => 'Nursing Task Overdue',
            'message' => ':task_type for :patient (admission :admission_number) was due :due_at.',
        ],
        'nursing_medication_due' => [
            'title' => 'Medication Due',
            'message' => ':medication for :patient (admission :admission_number) is due at :scheduled_at.',
        ],
        'nursing_medication_overdue' => [
            'title' => 'Medication Overdue',
            'message' => ':medication for :patient (admission :admission_number) was due at :scheduled_at.',
        ],
        'nursing_critical_observation' => [
            'title' => 'Critical Observation',
            'message' => ':observation_type for :patient (admission :admission_number) is :breach.',
        ],
        'nursing_handover_pending' => [
            'title' => 'Handover Pending Acknowledgement',
            'message' => 'A shift handover for :patient (admission :admission_number) is awaiting your acknowledgement.',
        ],
        'nursing_escalation_created' => [
            'title' => 'Nursing Escalation',
            'message' => ':concern for :patient (admission :admission_number) — severity :severity.',
        ],
        'nursing_escalation_resolved' => [
            'title' => 'Escalation Resolved',
            'message' => 'The escalation for :patient (admission :admission_number) has been resolved.',
        ],
    ];

    /**
     * @param  User|iterable  $recipients
     */
    public function send($recipients, string $template, array $params = [], array $channels = ['database']): void
    {
        $rendered = $this->render($template, $params);

        $users = $recipients instanceof User ? [$recipients] : $recipients;

        foreach ($users as $user) {
            if (in_array('database', $channels)) {
                Notification::create([
                    'user_id' => $user->id,
                    'channel' => 'database',
                    'type' => 'database',
                    'title' => $rendered['title'],
                    'message' => $rendered['message'],
                    'data' => $params,
                ]);
            }

            if (in_array('mail', $channels) && $user->email) {
                Mail::to($user->email)->queue(new SystemNotification(
                    $rendered['title'],
                    $rendered['message'],
                    $params
                ));
            }
        }
    }

    public function render(string $template, array $params): array
    {
        $base = $this->templates[$template] ?? ['title' => $template, 'message' => ''];

        return [
            'title' => $this->interpolate($base['title'], $params),
            'message' => $this->interpolate($base['message'], $params),
        ];
    }

    public function templates(): array
    {
        return $this->templates;
    }

    public function unreadForUser(User $user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->latest()
            ->get();
    }

    public function recentForUser(User $user, int $limit = 20): Collection
    {
        return Notification::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function markAllRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    protected function interpolate(string $string, array $params): string
    {
        foreach ($params as $key => $value) {
            $string = str_replace(':'.$key, $value, $string);
        }

        return $string;
    }
}
