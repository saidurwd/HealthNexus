<?php

namespace App\Services\Analytics\Semantic;

/**
 * The semantic model: every fact, the ways it can be cut (dimensions) and the numbers that can be asked of it (measures), declared once. Dashboards, KPIs and reports are queries over this
 * model; nothing outside it can be asked for. Expressions here are written by us, never taken from a user (users choose keys, not SQL).
 *
 * Measure forms: count · sum(col) · avg(col) · distinct(col) · expr(sql over the fact alias f) · ratio(num, den, scale). `when` narrows a measure to rows matching a condition.
 * `small` marks a count of events about people: small numbers of it are hidden from people without the sensitive-data right.
 */
class SemanticCatalog
{
    /** @return array<string,array<string,mixed>> */
    public static function facts(): array
    {
        $branch = ['col' => 'branch_id', 'label' => 'Branch', 'join' => ['dim_branch', 'branch_id', 'name']];
        $dept = ['col' => 'department_id', 'label' => 'Department', 'join' => ['dim_department', 'department_id', 'name']];
        $provider = ['col' => 'provider_id', 'label' => 'Doctor', 'join' => ['dim_provider', 'provider_id', 'name']];
        $plain = fn (string $col, string $label) => ['col' => $col, 'label' => $label];
        $scope = ['organization' => 'organization_id', 'branch' => 'branch_id'];
        $day = ['col' => 'date_key', 'type' => 'key'];

        return [
            'invoices' => ['table' => 'fact_invoices', 'label' => 'Invoices', 'date' => $day, 'scope' => $scope, 'base' => 'f.is_billed = 1',
                'dimensions' => ['branch' => $branch, 'payer_kind' => $plain('payer_kind', 'Paid by'), 'invoice_type' => $plain('invoice_type', 'Invoice type'), 'status' => $plain('status', 'Status')],
                'measures' => [
                    'invoice_count' => ['label' => 'Invoices', 'agg' => 'count', 'unit' => 'count', 'small' => true],
                    'billed' => ['label' => 'Billed', 'agg' => 'sum', 'col' => 'grand_total', 'unit' => 'money'],
                    'collected' => ['label' => 'Collected', 'agg' => 'sum', 'col' => 'paid', 'unit' => 'money'],
                    'outstanding' => ['label' => 'Still due', 'agg' => 'sum', 'col' => 'due', 'unit' => 'money'],
                    'discount' => ['label' => 'Discounts given', 'agg' => 'sum', 'col' => 'discount', 'unit' => 'money'],
                    'collection_rate' => ['label' => 'Collection rate', 'agg' => 'ratio', 'num' => 'collected', 'den' => 'billed', 'scale' => 100, 'unit' => 'percent'],
                    'avg_invoice' => ['label' => 'Average invoice', 'agg' => 'avg', 'col' => 'grand_total', 'unit' => 'money'],
                ]],
            'revenue' => ['table' => 'fact_revenue_lines', 'label' => 'Revenue by service', 'date' => $day, 'scope' => $scope, 'base' => 'f.is_billed = 1',
                'dimensions' => ['branch' => $branch, 'department' => $dept, 'doctor' => $provider, 'category' => $plain('category_code', 'Kind of service')],
                'measures' => [
                    'net_revenue' => ['label' => 'Net revenue', 'agg' => 'sum', 'col' => 'net', 'unit' => 'money'],
                    'gross_revenue' => ['label' => 'Gross revenue', 'agg' => 'sum', 'col' => 'gross', 'unit' => 'money'],
                    'discounts' => ['label' => 'Discounts', 'agg' => 'sum', 'col' => 'discount', 'unit' => 'money'],
                    'revenue_lines' => ['label' => 'Service lines', 'agg' => 'count', 'unit' => 'count'],
                ]],
            'payments' => ['table' => 'fact_payments', 'label' => 'Payments', 'date' => $day, 'scope' => $scope, 'base' => "f.status = 'completed'",
                'dimensions' => ['branch' => $branch, 'method' => $plain('method_type', 'Payment method')],
                'measures' => ['payments_total' => ['label' => 'Received', 'agg' => 'sum', 'col' => 'amount', 'unit' => 'money'], 'payment_count' => ['label' => 'Payments', 'agg' => 'count', 'unit' => 'count']]],
            'encounters' => ['table' => 'fact_encounters', 'label' => 'Visits', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'department' => $dept, 'doctor' => $provider, 'visit_type' => $plain('encounter_type', 'Kind of visit'), 'priority' => $plain('priority', 'Priority'), 'source' => $plain('source', 'How they came'), 'status' => $plain('status', 'Status'), 'hour' => $plain('hour', 'Hour of day')],
                'measures' => [
                    'visits' => ['label' => 'Visits', 'agg' => 'count', 'unit' => 'count', 'small' => true],
                    'opd_visits' => ['label' => 'Outpatient visits', 'agg' => 'count', 'when' => "lower(f.encounter_type) in ('opd', 'outpatient')", 'unit' => 'count', 'small' => true],
                    'completed_visits' => ['label' => 'Completed visits', 'agg' => 'sum', 'col' => 'completed', 'unit' => 'count', 'small' => true],
                    'avg_visit_minutes' => ['label' => 'Average visit length (min)', 'agg' => 'avg', 'col' => 'duration_minutes', 'unit' => 'minutes'],
                ]],
            'appointments' => ['table' => 'fact_appointments', 'label' => 'Appointments', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'doctor' => $provider, 'type' => $plain('type', 'Type'), 'source' => $plain('source', 'Booked through'), 'status' => $plain('status', 'Status')],
                'measures' => [
                    'appointments' => ['label' => 'Appointments', 'agg' => 'count', 'unit' => 'count', 'small' => true],
                    'no_shows' => ['label' => 'No-shows', 'agg' => 'sum', 'col' => 'is_no_show', 'unit' => 'count', 'small' => true],
                    'cancellations' => ['label' => 'Cancellations', 'agg' => 'sum', 'col' => 'is_cancelled', 'unit' => 'count', 'small' => true],
                    'no_show_rate' => ['label' => 'No-show rate', 'agg' => 'ratio', 'num' => 'no_shows', 'den' => 'appointments', 'scale' => 100, 'unit' => 'percent'],
                    'cancellation_rate' => ['label' => 'Cancellation rate', 'agg' => 'ratio', 'num' => 'cancellations', 'den' => 'appointments', 'scale' => 100, 'unit' => 'percent'],
                    'avg_wait' => ['label' => 'Average wait (min)', 'agg' => 'avg', 'col' => 'wait_minutes', 'unit' => 'minutes'],
                    'avg_lead_days' => ['label' => 'Booked ahead (days)', 'agg' => 'avg', 'col' => 'lead_days', 'unit' => 'days'],
                ]],
            'lab' => ['table' => 'fact_lab_orders', 'label' => 'Laboratory', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'department' => $dept, 'priority' => $plain('priority', 'Priority'), 'status' => $plain('status', 'Status')],
                'measures' => [
                    'lab_orders' => ['label' => 'Lab orders', 'agg' => 'count', 'unit' => 'count', 'small' => true],
                    'lab_tat' => ['label' => 'Order to report (min)', 'agg' => 'avg', 'col' => 'tat_report_min', 'unit' => 'minutes'],
                    'lab_collect_tat' => ['label' => 'Order to sample (min)', 'agg' => 'avg', 'col' => 'tat_collect_min', 'unit' => 'minutes'],
                    'tests' => ['label' => 'Tests', 'agg' => 'sum', 'col' => 'items', 'unit' => 'count'],
                    'critical_results' => ['label' => 'Critical results', 'agg' => 'sum', 'col' => 'critical_results', 'unit' => 'count', 'small' => true],
                    'critical_rate' => ['label' => 'Critical result rate', 'agg' => 'ratio', 'num' => 'critical_results', 'den' => 'tests', 'scale' => 100, 'unit' => 'percent'],
                ]],
            'radiology' => ['table' => 'fact_radiology_exams', 'label' => 'Imaging', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'priority' => $plain('priority', 'Priority'), 'status' => $plain('status', 'Status')],
                'measures' => ['exams' => ['label' => 'Examinations', 'agg' => 'count', 'unit' => 'count', 'small' => true], 'rad_tat' => ['label' => 'Order to report (min)', 'agg' => 'avg', 'col' => 'tat_report_min', 'unit' => 'minutes']]],
            'pharmacy' => ['table' => 'fact_dispensings', 'label' => 'Pharmacy dispensing', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'status' => $plain('status', 'Status')],
                'measures' => ['dispensings' => ['label' => 'Dispensings', 'agg' => 'count', 'unit' => 'count', 'small' => true], 'items_dispensed' => ['label' => 'Medicines handed out', 'agg' => 'sum', 'col' => 'items', 'unit' => 'count'],
                    'dispensing_tat' => ['label' => 'Prescription to hand-over (min)', 'agg' => 'avg', 'col' => 'tat_minutes', 'unit' => 'minutes']]],
            'admissions' => ['table' => 'fact_admissions', 'label' => 'Inpatient admissions', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'department' => $dept, 'doctor' => $provider, 'status' => $plain('status', 'Status')],
                'measures' => ['admissions' => ['label' => 'Admissions', 'agg' => 'count', 'unit' => 'count', 'small' => true], 'discharges' => ['label' => 'Discharges', 'agg' => 'sum', 'col' => 'is_discharged', 'unit' => 'count', 'small' => true],
                    'avg_los' => ['label' => 'Average stay (days)', 'agg' => 'avg', 'col' => 'los_days', 'unit' => 'days']]],
            'beds' => ['table' => 'fact_bed_census', 'label' => 'Bed occupancy', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch],
                'measures' => ['occupied_bed_days' => ['label' => 'Occupied bed-days', 'agg' => 'sum', 'col' => 'beds_occupied', 'unit' => 'count'], 'available_bed_days' => ['label' => 'Available bed-days', 'agg' => 'sum', 'col' => 'beds_total', 'unit' => 'count'],
                    'occupancy_rate' => ['label' => 'Bed occupancy', 'agg' => 'ratio', 'num' => 'occupied_bed_days', 'den' => 'available_bed_days', 'scale' => 100, 'unit' => 'percent']]],
            'surgery' => ['table' => 'fact_surgeries', 'label' => 'Operations', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'priority' => $plain('priority', 'Priority'), 'surgery_type' => $plain('surgery_type', 'Kind'), 'status' => $plain('status', 'Status')],
                'measures' => ['surgeries' => ['label' => 'Operations booked', 'agg' => 'count', 'unit' => 'count', 'small' => true], 'surgeries_done' => ['label' => 'Operations done', 'agg' => 'sum', 'col' => 'completed', 'unit' => 'count', 'small' => true],
                    'surgeries_cancelled' => ['label' => 'Operations cancelled', 'agg' => 'sum', 'col' => 'cancelled', 'unit' => 'count', 'small' => true],
                    'cancellation_rate' => ['label' => 'Cancellation rate', 'agg' => 'ratio', 'num' => 'surgeries_cancelled', 'den' => 'surgeries', 'scale' => 100, 'unit' => 'percent'],
                    'ot_start_delay' => ['label' => 'Start delay (min)', 'agg' => 'avg', 'col' => 'start_delay_minutes', 'unit' => 'minutes'], 'ot_duration' => ['label' => 'Operation length (min)', 'agg' => 'avg', 'col' => 'actual_minutes', 'unit' => 'minutes']]],
            'icu' => ['table' => 'fact_icu_stays', 'label' => 'Intensive care', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'status' => $plain('status', 'Status')],
                'measures' => ['icu_stays' => ['label' => 'ICU stays', 'agg' => 'count', 'unit' => 'count', 'small' => true], 'icu_los' => ['label' => 'Average ICU stay (hours)', 'agg' => 'avg', 'col' => 'los_hours', 'unit' => 'hours']]],
            'emergency' => ['table' => 'fact_ed_visits', 'label' => 'Emergency', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'triage_level' => $plain('triage_level', 'Triage level'), 'disposition' => $plain('disposition_type', 'How it ended'), 'hour' => $plain('hour', 'Hour of day')],
                'measures' => ['ed_visits' => ['label' => 'Emergency visits', 'agg' => 'count', 'unit' => 'count', 'small' => true], 'door_to_doctor' => ['label' => 'Door to doctor (min)', 'agg' => 'avg', 'col' => 'door_to_doctor_min', 'unit' => 'minutes'],
                    'door_to_triage' => ['label' => 'Door to triage (min)', 'agg' => 'avg', 'col' => 'door_to_triage_min', 'unit' => 'minutes'], 'ed_los' => ['label' => 'Time in emergency (min)', 'agg' => 'avg', 'col' => 'los_minutes', 'unit' => 'minutes']]],
            'claims' => ['table' => 'fact_claims', 'label' => 'Insurance claims', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => $branch, 'payer' => $plain('payer_id', 'Insurer'), 'status' => $plain('status', 'Status')],
                'measures' => ['claims' => ['label' => 'Claims', 'agg' => 'count', 'unit' => 'count'], 'claimed' => ['label' => 'Claimed from insurers', 'agg' => 'sum', 'col' => 'payer_share', 'unit' => 'money'], 'claims_paid' => ['label' => 'Paid by insurers', 'agg' => 'sum', 'col' => 'paid', 'unit' => 'money'],
                    'claims_outstanding' => ['label' => 'Insurer claims still unpaid', 'agg' => 'sum', 'col' => 'outstanding', 'unit' => 'money'], 'denied_claims' => ['label' => 'Denied claims', 'agg' => 'sum', 'col' => 'is_denied', 'unit' => 'count'],
                    'denial_rate' => ['label' => 'Denial rate', 'agg' => 'ratio', 'num' => 'denied_claims', 'den' => 'claims', 'scale' => 100, 'unit' => 'percent'], 'avg_open_age' => ['label' => 'Age of open claims (days)', 'agg' => 'avg', 'col' => 'age_days', 'when' => 'f.is_open = 1', 'unit' => 'days']]],
            'stock' => ['table' => 'fact_stock_daily', 'label' => 'Stock-outs', 'date' => $day, 'scope' => ['organization' => 'organization_id'],
                'dimensions' => ['store' => ['col' => 'store_id', 'label' => 'Store']],
                'measures' => ['items_tracked' => ['label' => 'Items stocked', 'agg' => 'sum', 'col' => 'items_tracked', 'unit' => 'count'], 'items_out' => ['label' => 'Items out of stock', 'agg' => 'sum', 'col' => 'items_out', 'unit' => 'count'],
                    'stockout_rate' => ['label' => 'Stock-out rate', 'agg' => 'ratio', 'num' => 'items_out', 'den' => 'items_tracked', 'scale' => 100, 'unit' => 'percent']]],
            'daily' => ['table' => 'fact_daily_metrics', 'label' => 'Daily counts', 'date' => $day, 'scope' => $scope,
                'dimensions' => ['branch' => ['col' => 'branch_id', 'label' => 'Branch', 'join' => ['dim_branch', 'branch_id', 'name']], 'metric' => $plain('metric', 'Count')],
                'measures' => [
                    'value' => ['label' => 'Value', 'agg' => 'sum', 'col' => 'value', 'unit' => 'count'],
                    'new_patients' => ['label' => 'New patients', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'patients_registered'", 'unit' => 'count', 'small' => true],
                    'hr_attended' => ['label' => 'Staff present', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'hr_attended'", 'unit' => 'count'],
                    'hr_absent' => ['label' => 'Staff absent', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'hr_absent'", 'unit' => 'count'],
                    'hr_late' => ['label' => 'Late arrivals', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'hr_late'", 'unit' => 'count'],
                    'staff_days' => ['label' => 'Staff working days', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric in ('hr_attended', 'hr_absent')", 'unit' => 'count'],
                    'attendance_rate' => ['label' => 'Attendance rate', 'agg' => 'ratio', 'num' => 'hr_attended', 'den' => 'staff_days', 'scale' => 100, 'unit' => 'percent'],
                    'ratings_sum' => ['label' => 'Rating points', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'ratings_sum'", 'unit' => 'count'],
                    'ratings_count' => ['label' => 'Ratings', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'ratings_count'", 'unit' => 'count', 'small' => true],
                    'avg_rating' => ['label' => 'Average visit rating (1-5)', 'agg' => 'ratio', 'num' => 'ratings_sum', 'den' => 'ratings_count', 'scale' => 1, 'unit' => 'ratio'],
                    'complaints' => ['label' => 'Complaints opened', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'complaints_opened'", 'unit' => 'count'],
                    'blood_donations' => ['label' => 'Blood donations', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'blood_donations'", 'unit' => 'count'],
                    'blood_units_issued' => ['label' => 'Blood units issued', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'blood_units_issued'", 'unit' => 'count'],
                    'online_payments' => ['label' => 'Online payments', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'portal_payments'", 'unit' => 'count'],
                    'messages_out' => ['label' => 'Messages sent to other systems', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'messages_out'", 'unit' => 'count'],
                    'messages_dead' => ['label' => 'Messages that failed for good', 'agg' => 'sum', 'col' => 'value', 'when' => "f.metric = 'messages_dead'", 'unit' => 'count'],
                ]],
            'ledger' => ['table' => 'fact_ledger_monthly', 'label' => 'Books of account', 'date' => ['col' => 'period_ym', 'type' => 'ym'], 'scope' => ['organization' => 'organization_id'],
                'dimensions' => ['account' => $plain('account_code', 'Account'), 'account_type' => $plain('account_type', 'Kind of account')],
                'measures' => [
                    'ledger_revenue' => ['label' => 'Revenue (books)', 'agg' => 'expr', 'expr' => "case when f.account_type = 'revenue' then f.credit - f.debit else 0 end", 'unit' => 'money'],
                    'ledger_expense' => ['label' => 'Expenses (books)', 'agg' => 'expr', 'expr' => "case when f.account_type = 'expense' then f.debit - f.credit else 0 end", 'unit' => 'money'],
                    'receivables_balance' => ['label' => 'Owed to the hospital (books)', 'agg' => 'sum', 'col' => 'closing', 'when' => "f.account_code in ('1210', '1220', '1230')", 'unit' => 'money', 'snapshot' => true],
                    'payables_balance' => ['label' => 'Owed to suppliers (books)', 'agg' => 'sum', 'col' => 'closing', 'when' => "f.account_code = '2100'", 'unit' => 'money', 'snapshot' => true],
                    'cash_balance' => ['label' => 'Cash and bank (books)', 'agg' => 'sum', 'col' => 'closing', 'when' => "f.account_code in ('1110', '1120', '1130', '1140')", 'unit' => 'money', 'snapshot' => true],
                ]],
        ];
    }

    public static function fact(string $key): ?array
    {
        return self::facts()[$key] ?? null;
    }

    /** What the screens may offer: keys and labels only. @return list<array{key:string,label:string,dimensions:array<string,string>,measures:array<string,array{label:string,unit:string}>}> */
    public static function describe(): array
    {
        $out = [];
        foreach (self::facts() as $key => $f) {
            $out[] = ['key' => $key, 'label' => $f['label'], 'dimensions' => array_map(fn ($d) => $d['label'], $f['dimensions']), 'measures' => array_map(fn ($m) => ['label' => $m['label'], 'unit' => $m['unit']], $f['measures'])];
        }

        return $out;
    }
}
