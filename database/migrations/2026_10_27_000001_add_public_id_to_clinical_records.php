<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * H-12 steps 2–3: a ULID `public_id` for every patient-identifying, clinical-event and money-event record that appears in a URL
 * (guideline 9.1). Nullable → back-filled → unique, as in the first step. Raw inserts that skip the model get a public id the first
 * time the record is linked (see HasPublicId::getRouteKey).
 */
return new class extends Migration
{
    private const TABLES = [
        'patient_alerts',
        'patient_allergies',
        'patient_amendments',
        'patient_consents',
        'patient_documents',
        'patient_histories',
        'patient_duplicate_candidates',
        'appointment_documents',
        'prescriptions',
        'encounter_amendments',
        'billing_invoices',
        'billing_payments',
        'billing_receipts',
        'billing_refunds',
        'billing_adjustments',
        'billing_charges',
        'billing_cashier_sessions',
        'lab_orders',
        'lab_order_items',
        'lab_results',
        'lab_specimens',
        'lab_reports',
        'lab_critical_result_alerts',
        'radiology_orders',
        'radiology_order_items',
        'radiology_examinations',
        'radiology_reports',
        'radiology_studies',
        'radiology_critical_findings',
        'pharmacy_orders',
        'pharmacy_dispensings',
        'pharmacy_safety_alerts',
        'ipd_admissions',
        'ipd_admission_requests',
        'ipd_bed_movements',
        'ipd_discharge_requests',
        'ipd_patient_leaves',
        'nursing_episodes',
        'nursing_assessments',
        'nursing_care_plans',
        'nursing_discharge_checklists',
        'nursing_escalations',
        'nursing_handover',
        'nursing_medication_administrations',
        'nursing_notes',
        'nursing_tasks',
        'nursing_assignments',
        'nursing_devices',
        'ot_surgeries',
        'ot_surgery_requests',
        'ot_operative_notes',
        'ot_preoperative_assessments',
        'ot_preoperative_checklists',
        'ot_preoperative_checklist_items',
        'ot_recovery_records',
        'ot_surgical_safety_checklists',
        'ot_surgical_safety_checklist_items',
        'ot_surgical_team',
        'icu_admission_requests',
        'icu_assessments',
        'icu_critical_alerts',
        'icu_critical_events',
        'icu_devices',
        'icu_discharge_summaries',
        'icu_episodes',
        'icu_infusions',
        'icu_progress_notes',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'public_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->char('public_id', 26)->nullable()->after('id'));
            }

            DB::table($table)->whereNull('public_id')->orderBy('id')->chunkById(1000, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update(['public_id' => (string) Str::ulid()]);
                }
            });

            Schema::table($table, fn (Blueprint $t) => $t->unique('public_id', "uq_{$table}_public_id"));
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique("uq_{$table}_public_id");
                $t->dropColumn('public_id');
            });
        }
    }
};
