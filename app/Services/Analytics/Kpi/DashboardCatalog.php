<?php

namespace App\Services\Analytics\Kpi;

/** The five role dashboards that come with the module. A widget is a KPI tile, a chart or a table; charts and tables are semantic queries over the last 30 days unless they say otherwise. */
class DashboardCatalog
{
    /** @return list<array{code:string,name:string,role_key:string,widgets:list<array<string,mixed>>}> */
    public static function system(): array
    {
        $kpi = fn (string $code) => ['type' => 'kpi', 'kpi' => $code];
        $chart = fn (string $title, string $kind, array $query) => ['type' => 'chart', 'title' => $title, 'kind' => $kind, 'query' => $query];
        $table = fn (string $title, array $query) => ['type' => 'table', 'title' => $title, 'query' => $query];

        return [
            ['code' => 'ceo', 'name' => 'Chief executive', 'role_key' => 'ceo', 'widgets' => [
                $kpi('net_revenue'), $kpi('collection_rate'), $kpi('opd_visits'), $kpi('bed_occupancy'), $kpi('avg_rating'), $kpi('claim_denial_rate'),
                $chart('Net revenue by day', 'line', ['fact' => 'revenue', 'measures' => ['net_revenue'], 'grain' => 'day']),
                $chart('Outpatient visits by day', 'line', ['fact' => 'encounters', 'measures' => ['opd_visits'], 'grain' => 'day']),
                $table('Revenue by department', ['fact' => 'revenue', 'measures' => ['net_revenue', 'gross_revenue'], 'group_by' => ['department'], 'limit' => 12]),
            ]],
            ['code' => 'medical_director', 'name' => 'Medical director', 'role_key' => 'medical_director', 'widgets' => [
                $kpi('admissions'), $kpi('avg_los'), $kpi('ed_door_to_doctor'), $kpi('surgeries_done'), $kpi('surgery_cancellation_rate'), $kpi('lab_tat'), $kpi('radiology_tat'), $kpi('icu_avg_los'),
                $chart('Admissions by day', 'line', ['fact' => 'admissions', 'measures' => ['admissions'], 'grain' => 'day']),
                $chart('Emergency visits by hour of the day', 'bar', ['fact' => 'emergency', 'measures' => ['ed_visits'], 'group_by' => ['hour'], 'order_by' => 'hour', 'direction' => 'asc']),
                $table('Operations by kind', ['fact' => 'surgery', 'measures' => ['surgeries', 'surgeries_done', 'surgeries_cancelled'], 'group_by' => ['surgery_type']]),
            ]],
            ['code' => 'finance', 'name' => 'Finance', 'role_key' => 'finance', 'widgets' => [
                $kpi('net_revenue'), $kpi('collection_rate'), $kpi('outstanding_dues'), $kpi('open_claims_amount'), $kpi('claim_denial_rate'),
                $chart('Billed and collected by month', 'bar', ['fact' => 'invoices', 'measures' => ['billed', 'collected'], 'grain' => 'month']),
                $chart('Revenue by kind of service', 'bar', ['fact' => 'revenue', 'measures' => ['net_revenue'], 'group_by' => ['category'], 'limit' => 10]),
                $table('Claims by status', ['fact' => 'claims', 'measures' => ['claims', 'claimed', 'claims_paid', 'claims_outstanding'], 'group_by' => ['status']]),
                $table('Money in the books by month', ['fact' => 'ledger', 'measures' => ['ledger_revenue', 'ledger_expense'], 'grain' => 'month']),
            ]],
            ['code' => 'nursing', 'name' => 'Nursing and wards', 'role_key' => 'nursing', 'widgets' => [
                $kpi('bed_occupancy'), $kpi('avg_los'), $kpi('admissions'), $kpi('icu_avg_los'), $kpi('attendance_rate'),
                $chart('Bed occupancy by day', 'line', ['fact' => 'beds', 'measures' => ['occupancy_rate'], 'grain' => 'day']),
                $table('Admissions by department', ['fact' => 'admissions', 'measures' => ['admissions', 'discharges', 'avg_los'], 'group_by' => ['department']]),
            ]],
            ['code' => 'pharmacy', 'name' => 'Pharmacy and stores', 'role_key' => 'pharmacy', 'widgets' => [
                $kpi('dispensing_tat'), $kpi('stockout_rate'),
                $chart('Prescriptions dispensed by day', 'line', ['fact' => 'pharmacy', 'measures' => ['dispensings'], 'grain' => 'day']),
                $table('Dispensing by status', ['fact' => 'pharmacy', 'measures' => ['dispensings', 'items_dispensed', 'dispensing_tat'], 'group_by' => ['status']]),
            ]],
        ];
    }
}
