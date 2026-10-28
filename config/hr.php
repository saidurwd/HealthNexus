<?php

/*
| HR, workforce and payroll defaults (Phase 16). EVERY rate, slab, cap and entitlement here is an EXAMPLE to be confirmed by the HR manager,
| the accountant and a lawyer (OD-16) — the Labour Act, the Income Tax Ordinance and the hospital's own policy decide the real values.
| Nothing is hard-coded in code: the hospital's values are set in Settings (`hr.<key>`) and, for tax slabs and salary structures, in
| effective-dated tables. These defaults only fill an empty system.
*/
return [
    'employee_categories' => ['permanent', 'contract', 'part_time', 'visiting', 'intern', 'casual'],

    'employee_statuses' => ['onboarding', 'active', 'on_leave', 'suspended', 'resigned', 'terminated', 'retired'],

    // Who still counts as working here (rosters, attendance, payroll).
    'working_statuses' => ['active', 'on_leave'],

    'weekly_off' => 'fri',

    'job_families' => ['clinical', 'nursing', 'allied', 'admin', 'support'],

    'credential_types' => ['bmdc', 'bnmc', 'pharmacy_council', 'dental_council', 'bls', 'acls', 'other'],

    // A clinical duty can be refused to someone whose licence has lapsed — only when the hospital turns this on (guideline override 6).
    'block_expired_credentials' => false,

    'credential_alert_days' => [90, 30, 7],

    'document_types' => ['nid', 'cv', 'photo', 'certificate', 'offer_letter', 'contract', 'other'],

    'onboarding_items' => [
        ['code' => 'documents', 'title' => 'Collect identity, certificates and photographs', 'owner_role' => 'hr_officer'],
        ['code' => 'contract', 'title' => 'Contract signed', 'owner_role' => 'hr_officer'],
        ['code' => 'credentials', 'title' => 'Professional licence checked with the council', 'owner_role' => 'hr_officer', 'required' => false],
        ['code' => 'bank', 'title' => 'Bank account or mobile wallet recorded for salary', 'owner_role' => 'payroll_officer'],
        ['code' => 'login', 'title' => 'System login created with the right role', 'owner_role' => 'hr_manager'],
        ['code' => 'induction', 'title' => 'Induction and fire safety briefing', 'owner_role' => 'department_manager'],
    ],

    'offboarding_items' => [
        ['code' => 'handover', 'title' => 'Work handed over', 'owner_role' => 'department_manager'],
        ['code' => 'assets', 'title' => 'Badge, keys and equipment returned', 'owner_role' => 'hr_officer'],
        ['code' => 'loans', 'title' => 'Loans and advances settled', 'owner_role' => 'payroll_officer'],
        ['code' => 'final_pay', 'title' => 'Final pay prepared', 'owner_role' => 'payroll_officer'],
        ['code' => 'exit_interview', 'title' => 'Exit interview held', 'owner_role' => 'hr_officer', 'required' => false],
    ],

    // Roster (S04)
    'roster' => ['min_rest_hours' => 11, 'max_weekly_hours' => 60, 'max_consecutive_days' => 7],

    // Attendance (S05)
    'attendance' => [
        'punch_window_before_hours' => 3,   // a punch up to this long before a shift starts belongs to it
        'punch_window_after_hours' => 6,    // … and up to this long after it ends
        'duplicate_punch_minutes' => 2,     // a second punch this close to the first is the same touch of the finger
        'half_day_below_percent' => 50,     // worked less than this share of the shift = half day
        'overtime_min_minutes' => 30,       // time after the shift counts as overtime only from this much
        'unrostered_default_shift' => null, // code of the shift to assume for someone with no roster entry (null = unrostered day)
    ],

    // Leave (S06)
    'leave_types' => [
        ['code' => 'CL', 'name' => 'Casual leave', 'is_paid' => true, 'annual_days' => 10, 'accrual' => 'upfront', 'carry_forward_max' => 0, 'max_consecutive_days' => 3],
        ['code' => 'SL', 'name' => 'Sick leave', 'is_paid' => true, 'annual_days' => 14, 'accrual' => 'upfront', 'carry_forward_max' => 0, 'document_after_days' => 3],
        ['code' => 'EL', 'name' => 'Earned leave', 'is_paid' => true, 'annual_days' => 18, 'accrual' => 'monthly', 'carry_forward_max' => 30, 'encashable' => true],
        ['code' => 'ML', 'name' => 'Maternity leave', 'is_paid' => true, 'annual_days' => 112, 'accrual' => 'none', 'gender_restriction' => 'female', 'requires_hr_approval' => true, 'document_after_days' => 1],
        ['code' => 'PL', 'name' => 'Paternity leave', 'is_paid' => true, 'annual_days' => 7, 'accrual' => 'none', 'gender_restriction' => 'male', 'requires_hr_approval' => true],
        ['code' => 'LWP', 'name' => 'Leave without pay', 'is_paid' => false, 'annual_days' => 0, 'accrual' => 'none', 'requires_hr_approval' => true],
    ],

    // Overtime (S07)
    'overtime' => [
        'multipliers' => ['weekday' => 2.0, 'weekly_off' => 2.0, 'holiday' => 2.0],
        'daily_cap_minutes' => 240,
        'monthly_cap_minutes' => 3600,
        'hourly_divisor' => 208, // basic pay divided by this gives the hourly rate
    ],

    // Payroll (S09)
    'payroll' => [
        'provident_fund' => ['employee_percent' => 10, 'employer_percent' => 10, 'code' => 'PF'],
        'tax_exempt_annual' => 0,   // amount of yearly income exempt before the first slab (kept in the slabs themselves)
        'minimum_monthly_tax' => 0,
        'pay_month_basis' => 'calendar', // pay is divided over the calendar days of the month
    ],

    // Example slabs (effective-dated rows in hr_tax_slabs override these): [from, to|null, percent]
    'tax_slabs' => [[0, 350000, 0], [350000, 450000, 5], [450000, 750000, 10], [750000, 1150000, 15], [1150000, 1650000, 20], [1650000, null, 25]],

    'pay_components' => [
        ['code' => 'BASIC', 'name' => 'Basic pay', 'kind' => 'earning', 'method' => 'fixed', 'is_basic' => true, 'taxable' => true, 'prorate' => true],
        ['code' => 'HRENT', 'name' => 'House rent allowance', 'kind' => 'earning', 'method' => 'percent_basic', 'taxable' => true, 'prorate' => true],
        ['code' => 'MEDICAL', 'name' => 'Medical allowance', 'kind' => 'earning', 'method' => 'fixed', 'taxable' => false, 'prorate' => true],
        ['code' => 'CONV', 'name' => 'Conveyance', 'kind' => 'earning', 'method' => 'fixed', 'taxable' => false, 'prorate' => true],
        ['code' => 'DUTY', 'name' => 'Clinical duty allowance', 'kind' => 'earning', 'method' => 'fixed', 'taxable' => true, 'prorate' => true],
        ['code' => 'PF', 'name' => 'Provident fund (employee)', 'kind' => 'deduction', 'method' => 'percent_basic', 'taxable' => false, 'prorate' => false],
        ['code' => 'PFER', 'name' => 'Provident fund (employer)', 'kind' => 'employer', 'method' => 'percent_basic', 'taxable' => false, 'prorate' => false],
    ],

    'payment_methods' => ['bank', 'mobile_wallet'],

    'loan_kinds' => ['loan', 'advance'],

    'appraisal_scale' => [1, 5],
];
