<?php

/*
| Finance and Accounting (Phase 21). Everything here is a starting value: the hospital's accountant sets the real chart, the VAT/AIT rates and the rules
| (Settings `finance.<key>` win over this file). Nothing in here is tax advice (OD-03): rates and rules must be confirmed with the accountant.
*/
return [
    'currency' => 'BDT',

    // Journal dates are the hospital's local dates; times are stored in UTC.
    'timezone' => 'Asia/Dhaka',

    'journal' => [
        // A manual journal needs a second person to approve it. Turn off only for a one-person office.
        'maker_checker' => true,
        // The person who wrote a journal may not approve it, whatever their rights.
        'allow_self_approval' => false,
        'max_lines' => 200,
    ],

    'period' => [
        // Automatic postings may still enter a locked period when set (a late receipt). Off = they wait in the failed list until a person reopens the period.
        'post_into_locked' => false,
        'first_month' => 1, // the fiscal year starts in this month (Bangladesh government year is July; most hospitals use January: confirm)
    ],

    'posting' => [
        'retry_limit' => 20,
        'batch' => 200,
        // How far back the catch-up scan looks for Billing records that never posted (days).
        'catch_up_days' => 45,
    ],

    'receivable' => ['ageing_buckets' => [30, 60, 90], 'writeoff_second_approver' => true, 'writeoff_limit_without_second' => 0],

    'payable' => ['default_terms_days' => 30, 'payment_second_approver' => true],

    'tax' => [
        // Rates are not set here on purpose: tax codes are created by the accountant (Finance > Tax codes). The starter codes carry a rate of 0 until confirmed.
        'vat_registered' => false,
    ],

    'budget' => ['mode' => 'warn', 'commit_open_pos' => true], // mode: off | warn | block

    'asset' => ['default_life_months' => 60, 'method' => 'straight_line'],

    'reports' => ['fee_share_basis' => 'net_revenue'],

    // The starter chart (code, name, type, parent, postable, control). A hospital edits it freely; accounts made here are marked "system" so they are not deleted by mistake.
    'starter_chart' => [
        ['1000', 'Assets', 'asset', null, false],
        ['1100', 'Cash and bank', 'asset', '1000', false],
        ['1110', 'Cash in hand (cashier tills)', 'asset', '1100', true, 'cash'],
        ['1120', 'Bank accounts', 'asset', '1100', true, 'bank'],
        ['1130', 'Mobile wallets (bKash, Nagad)', 'asset', '1100', true, 'bank'],
        ['1140', 'Card and online payment clearing', 'asset', '1100', true, 'bank'],
        ['1200', 'Receivables', 'asset', '1000', false],
        ['1210', 'Patient receivables', 'asset', '1200', true, 'receivable'],
        ['1220', 'Corporate receivables', 'asset', '1200', true, 'receivable'],
        ['1230', 'Insurer receivables', 'asset', '1200', true, 'receivable'],
        ['1300', 'Inventory', 'asset', '1000', false],
        ['1310', 'Pharmacy stock', 'asset', '1300', true, 'inventory'],
        ['1320', 'Stores and consumables', 'asset', '1300', true, 'inventory'],
        ['1400', 'Prepayments and advances paid', 'asset', '1000', true],
        ['1450', 'VAT input (recoverable)', 'asset', '1000', true, 'tax'],
        ['1500', 'Fixed assets', 'asset', '1000', false],
        ['1510', 'Medical equipment', 'asset', '1500', true],
        ['1520', 'Furniture, vehicles and other', 'asset', '1500', true],
        ['1590', 'Accumulated depreciation', 'asset', '1500', true],
        ['2000', 'Liabilities', 'liability', null, false],
        ['2100', 'Accounts payable (suppliers)', 'liability', '2000', true, 'payable'],
        ['2150', 'Goods received, not yet invoiced', 'liability', '2000', true],
        ['2200', 'Patient advances and deposits', 'liability', '2000', true],
        ['2250', 'Refunds payable', 'liability', '2000', true],
        ['2300', 'VAT output payable', 'liability', '2000', true, 'tax'],
        ['2310', 'AIT and TDS payable', 'liability', '2000', true, 'tax'],
        ['2320', 'Salaries payable', 'liability', '2000', true],
        ['2330', 'Provident fund payable', 'liability', '2000', true],
        ['2360', 'Doctors fees payable', 'liability', '2000', true],
        ['2340', 'Other payroll deductions payable', 'liability', '2000', true],
        ['2350', 'Income tax withheld from salaries', 'liability', '2000', true],
        ['3000', 'Equity', 'equity', null, false],
        ['3100', 'Capital and reserves', 'equity', '3000', true],
        ['3200', 'Retained earnings', 'equity', '3000', true],
        ['3900', 'Opening balance equity', 'equity', '3000', true],
        ['4000', 'Revenue', 'revenue', null, false],
        ['4100', 'Outpatient services', 'revenue', '4000', true],
        ['4200', 'Inpatient services (beds, nursing)', 'revenue', '4000', true],
        ['4300', 'Laboratory', 'revenue', '4000', true],
        ['4400', 'Radiology', 'revenue', '4000', true],
        ['4500', 'Pharmacy sales', 'revenue', '4000', true],
        ['4600', 'Operation theatre and procedures', 'revenue', '4000', true],
        ['4700', 'Other services', 'revenue', '4000', true],
        ['4900', 'Discounts and concessions allowed', 'revenue', '4000', true],
        ['4950', 'Rounding differences', 'revenue', '4000', true],
        ['5000', 'Expenses', 'expense', null, false],
        ['5100', 'Salaries and employer contributions', 'expense', '5000', true],
        ['5150', 'Doctors professional fee share', 'expense', '5000', true],
        ['5200', 'Medicines and consumables used', 'expense', '5000', true],
        ['5300', 'Stock write-offs and adjustments', 'expense', '5000', true],
        ['5400', 'Depreciation', 'expense', '5000', true],
        ['5500', 'Bad debts written off', 'expense', '5000', true],
        ['5600', 'Bank and payment gateway charges', 'expense', '5000', true],
        ['5700', 'Insurer disallowances and contractual adjustments', 'expense', '5000', true],
        ['5800', 'Utilities, rent and other running costs', 'expense', '5000', true],
        ['5900', 'Cash over and short', 'expense', '5000', true],
    ],

    // role => [scope_key => account code]. 'all' is the fallback. Revenue per Billing category code is added on top by the starter set-up (keyword match) and can be edited.
    'starter_roles' => [
        'revenue' => ['all' => '4700'],
        'discount' => ['all' => '4900'],
        'rounding' => ['all' => '4950'],
        'ar_patient' => ['all' => '1210'],
        'ar_corporate' => ['all' => '1220'],
        'ar_insurer' => ['all' => '1230'],
        'cash' => ['all' => '1110'],
        'wallet' => ['all' => '1130'],
        'bank_default' => ['all' => '1120'],
        // 'payment_method' rows (scope = Billing payment method code) are added by the accountant; a method without one falls back to its type (cash, wallet, card, bank)
        'gateway_clearing' => ['all' => '1140'],
        'patient_advance' => ['all' => '2200'],
        'refund_payable' => ['all' => '2250'],
        'vat_output' => ['all' => '2300'],
        'vat_input' => ['all' => '1450'],
        'ait_payable' => ['all' => '2310'],
        'vds_payable' => ['all' => '2310'],
        'ap' => ['all' => '2100'],
        'grni' => ['all' => '2150'],
        'inventory' => ['all' => '1320', 'pharmacy' => '1310'],
        'stock_expense' => ['all' => '5200'],
        'stock_adjustment' => ['all' => '5300'],
        'bad_debt' => ['all' => '5500'],
        'disallowance' => ['all' => '5700'],
        'bank_charges' => ['all' => '5600'],
        'cash_over_short' => ['all' => '5900'],
        'depreciation_expense' => ['all' => '5400'],
        'accumulated_depreciation' => ['all' => '1590'],
        'asset_cost' => ['all' => '1510'],
        'fee_share_expense' => ['all' => '5150'],
        'fee_share_payable' => ['all' => '2360'],
        'retained_earnings' => ['all' => '3200'],
        'opening_equity' => ['all' => '3900'],
        // Payroll journal lines name their account by role (HrPayrollRun.journal.lines[].account)
        'payroll' => ['SALARY_EXPENSE' => '5100', 'SALARY_PAYABLE' => '2320', 'TAX_PAYABLE' => '2350', 'PF_PAYABLE' => '2330', 'LOAN_RECEIVABLE' => '1400', 'OTHER_DEDUCTIONS_PAYABLE' => '2340'],
    ],

    // Keywords of a Billing category (code or name, lower case) → revenue account code
    'revenue_keywords' => [
        '4300' => ['lab', 'patholog'], '4400' => ['radi', 'imaging', 'x-ray', 'xray', 'ultra'], '4500' => ['pharm', 'drug', 'medic'],
        '4600' => ['theatre', 'surg', 'proced', 'ot '], '4200' => ['bed', 'ward', 'room', 'ipd', 'nursing', 'icu', 'admission'], '4100' => ['consult', 'opd', 'visit', 'outpatient'],
    ],
];
