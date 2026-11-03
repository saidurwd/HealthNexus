<?php

/*
| Insurance and corporate claims defaults (Phase 14). EVERY rule here is an EXAMPLE for the billing / insurance officers to confirm (OD-14);
| the hospital's real values are set in Settings (`insurance.*`) and override these.
*/
return [
    // P-17: one register for every kind of payer; all use the same claim state machine.
    'payer_types' => ['private_insurer', 'tpa', 'corporate', 'government', 'ngo', 'charity', 'hospital_panel'],

    'coding_systems' => ['icd10', 'icd11'],

    'submission_methods' => ['file_export', 'portal_upload', 'email', 'api'],

    'filing_deadline_days' => 90,

    // Claim state machine: from => allowed next.
    'claim_transitions' => [
        'draft' => ['ready', 'cancelled'],
        'ready' => ['draft', 'submitted', 'cancelled'],
        'submitted' => ['acknowledged', 'queried', 'accepted', 'rejected', 'denied', 'partially_paid', 'paid'],
        'acknowledged' => ['queried', 'accepted', 'rejected', 'denied', 'partially_paid', 'paid'],
        'queried' => ['submitted', 'cancelled'],
        'accepted' => ['partially_paid', 'paid', 'denied'],
        'rejected' => ['cancelled'], // a rejected claim is replaced by a new version, never edited
        'denied' => ['appealed', 'written_off', 'cancelled'],
        'appealed' => ['accepted', 'denied', 'partially_paid', 'paid', 'written_off'],
        'partially_paid' => ['paid', 'written_off', 'denied'],
        'paid' => ['closed'],
        'written_off' => ['closed'],
        'cancelled' => [],
        'closed' => [],
    ],
    // States after which the claim's lines can no longer change (a new version is needed).
    'locked_states' => ['submitted', 'acknowledged', 'queried', 'accepted', 'rejected', 'denied', 'appealed', 'partially_paid', 'paid', 'written_off', 'closed'],

    // Example denial / rejection reason codes: code => [label, kind (administrative|clinical|financial), fix hint]
    'denial_reasons' => [
        'D01' => ['Policy not active on the service date', 'administrative', 'Check the policy dates and the visit date'],
        'D02' => ['Service not covered by the plan', 'clinical', 'Appeal with the clinical justification or bill the patient'],
        'D03' => ['Pre-authorization missing or exceeded', 'administrative', 'Attach the pre-authorization or request an extension'],
        'D04' => ['Diagnosis does not support the procedure', 'clinical', 'Add the supporting diagnosis or documents'],
        'D05' => ['Duplicate claim', 'administrative', 'Withdraw the duplicate'],
        'D06' => ['Filing deadline passed', 'administrative', 'Appeal with proof of timely first submission'],
        'D07' => ['Annual limit reached', 'financial', 'Bill the patient for the excess'],
        'D08' => ['Required document missing', 'administrative', 'Attach the missing document and resubmit'],
        'D09' => ['Rate above the agreed contract rate', 'financial', 'Correct to the contract rate'],
        'D99' => ['Other', 'administrative', 'Read the payer’s note'],
    ],

    // Documents that must be present before a claim is marked ready: claim type => list of [type, required]
    'document_checklist' => [
        'outpatient' => [['claim_form', true], ['invoice', true], ['prescription', false], ['lab_report', false]],
        'inpatient' => [['claim_form', true], ['invoice', true], ['discharge_summary', true], ['operation_note', false], ['lab_report', false]],
        'emergency' => [['claim_form', true], ['invoice', true], ['triage_record', false]],
    ],

    // Scrub rules (checked when a draft is made ready). key => label
    'scrub_rules' => [
        'policy_active' => 'The policy must be active on the service date',
        'diagnosis_present' => 'At least one coded diagnosis is needed',
        'preauth_present' => 'A valid pre-authorization is needed for this service',
        'within_filing_window' => 'The claim is past the payer’s filing deadline',
        'documents_complete' => 'Required documents are missing',
        'lines_tie_to_invoice' => 'Claim lines must add up to the invoice',
        'no_duplicate' => 'The same visit and payer already have an open claim',
    ],

    'aging_buckets' => [0, 30, 60, 90],

    'writeoff_requires_approval' => true,

    'preauth' => ['required_above' => 20000, 'valid_days' => 30],

    'alert_roles' => ['insurance_officer', 'claims_manager', 'accountant'],
];
