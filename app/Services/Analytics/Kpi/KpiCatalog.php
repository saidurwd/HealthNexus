<?php

namespace App\Services\Analytics\Kpi;

/**
 * The standard KPIs: the 25 numbers a hospital manager and a clinical director actually look at (guideline 26.8: start small). Each is a semantic query (a fact, one measure, optional filters)
 * plus what is good (up or down). Targets are examples for the hospital to set; leave them empty where the hospital has no target.
 */
class KpiCatalog
{
    /** @return list<array<string,mixed>> */
    public static function standard(): array
    {
        $k = fn (string $code, string $name, string $domain, string $unit, string $good, string $fact, string $measure, string $description, ?float $target = null, array $filters = []) => compact('code', 'name', 'domain', 'unit', 'good', 'fact', 'measure', 'description', 'target', 'filters');

        return [
            $k('opd_visits', 'Outpatient visits', 'operations', 'count', 'up', 'encounters', 'opd_visits', 'Outpatient visits in the period.'),
            $k('appointments_booked', 'Appointments booked', 'operations', 'count', 'up', 'appointments', 'appointments', 'Appointments with a date in the period.'),
            $k('no_show_rate', 'Appointment no-show rate', 'operations', 'percent', 'down', 'appointments', 'no_show_rate', 'Share of appointments where the patient did not come.', 10),
            $k('avg_wait', 'Average waiting time at the clinic', 'operations', 'minutes', 'down', 'appointments', 'avg_wait', 'Minutes from check-in to being seen.', 30),
            $k('new_patients', 'New patients registered', 'operations', 'count', 'up', 'daily', 'new_patients', 'Patients registered in the period.'),
            $k('admissions', 'Inpatient admissions', 'clinical', 'count', 'up', 'admissions', 'admissions', 'Patients admitted in the period.'),
            $k('avg_los', 'Average length of stay', 'clinical', 'days', 'down', 'admissions', 'avg_los', 'Days from admission to discharge, for patients discharged.', 4),
            $k('bed_occupancy', 'Bed occupancy', 'clinical', 'percent', 'up', 'beds', 'occupancy_rate', 'Occupied bed-days as a share of bed-days available.', 80),
            $k('icu_avg_los', 'Average intensive-care stay', 'clinical', 'hours', 'down', 'icu', 'icu_los', 'Hours from ICU admission to discharge.'),
            $k('ed_door_to_doctor', 'Emergency: arrival to doctor', 'clinical', 'minutes', 'down', 'emergency', 'door_to_doctor', 'Minutes from arrival to first seen by a doctor.', 30),
            $k('surgeries_done', 'Operations done', 'clinical', 'count', 'up', 'surgery', 'surgeries_done', 'Operations completed in the period.'),
            $k('ot_start_delay', 'Operating theatre start delay', 'clinical', 'minutes', 'down', 'surgery', 'ot_start_delay', 'Average minutes an operation started after its planned time.', 15),
            $k('surgery_cancellation_rate', 'Operation cancellation rate', 'clinical', 'percent', 'down', 'surgery', 'cancellation_rate', 'Share of booked operations that were cancelled.', 5),
            $k('lab_tat', 'Laboratory turn-around', 'diagnostics', 'minutes', 'down', 'lab', 'lab_tat', 'Minutes from order to final report.', 180),
            $k('lab_critical_rate', 'Critical result rate', 'diagnostics', 'percent', 'none', 'lab', 'critical_rate', 'Critical results as a share of tests (a measure of case mix, not of quality).'),
            $k('radiology_tat', 'Imaging turn-around', 'diagnostics', 'minutes', 'down', 'radiology', 'rad_tat', 'Minutes from order to approved report.', 240),
            $k('dispensing_tat', 'Pharmacy turn-around', 'pharmacy', 'minutes', 'down', 'pharmacy', 'dispensing_tat', 'Minutes from prescription to medicines handed over.', 20),
            $k('stockout_rate', 'Stock-out rate', 'pharmacy', 'percent', 'down', 'stock', 'stockout_rate', 'Items out of stock as a share of items stocked.', 3),
            $k('net_revenue', 'Net revenue', 'finance', 'money', 'up', 'revenue', 'net_revenue', 'Revenue from finalised bills, after discounts.'),
            $k('collection_rate', 'Collection rate', 'finance', 'percent', 'up', 'invoices', 'collection_rate', 'Share of billed money already collected.', 90),
            $k('outstanding_dues', 'Patient dues outstanding', 'finance', 'money', 'down', 'invoices', 'outstanding', 'Billed money still to be collected.'),
            $k('claim_denial_rate', 'Insurance claim denial rate', 'finance', 'percent', 'down', 'claims', 'denial_rate', 'Share of claims denied by the insurer.', 8),
            $k('open_claims_amount', 'Insurer claims still unpaid', 'finance', 'money', 'down', 'claims', 'claims_outstanding', 'Money insurers still owe on open claims.'),
            $k('attendance_rate', 'Staff attendance rate', 'people', 'percent', 'up', 'daily', 'attendance_rate', 'Days present as a share of days staff were expected.', 95),
            $k('avg_rating', 'Average visit rating', 'quality', 'ratio', 'up', 'daily', 'avg_rating', 'Average of patients\' ratings of their visit (1 to 5).', 4),
        ];
    }
}
