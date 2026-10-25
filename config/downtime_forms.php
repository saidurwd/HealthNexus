<?php

/*
| Paper forms for when the system is unavailable (guideline 21.6). Each form: title, "fields" (a labelled blank line each, in rows of two),
| and optional "table" (column headings + number of blank rows). Every label goes through __() so the form prints in English, Bangla or both.
*/
return [
    'admission' => [
        'title' => 'Admission form (downtime)',
        'fields' => ['Patient name', 'Patient number (MRN)', 'Date of birth / age', 'Sex', 'Phone', 'National ID', 'Address', 'Next of kin and phone', 'Allergies', 'Admitting doctor', 'Ward / bed', 'Reason for admission', 'Date and time of admission'],
    ],
    'prescription' => [
        'title' => 'Prescription (downtime)',
        'fields' => ['Patient name', 'Patient number (MRN)', 'Age / sex', 'Allergies', 'Doctor and registration number', 'Date'],
        'table' => ['columns' => ['Medicine', 'Dose', 'How often', 'How many days', 'Instructions'], 'rows' => 10],
        'footer' => ['Advice', 'Doctor signature'],
    ],
    'mar' => [
        'title' => 'Medication administration record (downtime)',
        'fields' => ['Patient name', 'Patient number (MRN)', 'Ward / bed', 'Allergies', 'Date'],
        'table' => ['columns' => ['Medicine', 'Dose', 'Route', 'Time due', 'Time given', 'Given by', 'Checked by', 'Not given: reason'], 'rows' => 14],
    ],
    'requisition' => [
        'title' => 'Test request (downtime)',
        'fields' => ['Patient name', 'Patient number (MRN)', 'Age / sex', 'Ward / department', 'Requesting doctor', 'Date and time', 'Clinical details'],
        'table' => ['columns' => ['Test or scan requested', 'Urgent? (tick)', 'Specimen / site', 'Collected by', 'Time collected'], 'rows' => 10],
    ],
    'receipt' => [
        'title' => 'Payment receipt (downtime)',
        'fields' => ['Receipt number (pre-printed)', 'Date and time', 'Patient name', 'Patient number (MRN)', 'Paid for', 'Amount', 'Payment method', 'Received by (cashier)'],
        'footer' => ['Cashier signature'],
    ],
    'triage' => [
        'title' => 'Triage record (downtime)',
        'fields' => ['Patient name (or "unknown")', 'Estimated age / sex', 'Arrival date and time', 'Arrived by', 'Main problem', 'Temperature', 'Pulse', 'Blood pressure', 'Breathing rate', 'Oxygen level', 'Pain score', 'Allergies', 'Priority given', 'Triaged by'],
    ],
    'vitals' => [
        'title' => 'Vital signs chart (downtime)',
        'fields' => ['Patient name', 'Patient number (MRN)', 'Ward / bed'],
        'table' => ['columns' => ['Date', 'Time', 'Temperature', 'Pulse', 'Blood pressure', 'Breathing rate', 'Oxygen level', 'Pain score', 'Recorded by'], 'rows' => 18],
    ],
];
