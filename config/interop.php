<?php

use App\Events\Appointments\AppointmentCreated;
use App\Events\Billing\InvoiceFinalized;
use App\Events\Clinical\EncounterCompleted;
use App\Events\Clinical\PrescriptionIssued;
use App\Events\Laboratory\LabReportFinalized;
use App\Events\PatientRegistered;

/*
| Interoperability defaults (Phase 18). Profile rules, retry timings and retention are EXAMPLES for the hospital's integration lead and the privacy officer to confirm (OD-18).
| Settings `interop.<key>` override these.
*/
return [
    'partner_kinds' => ['fhir_client', 'fhir_server', 'hl7_peer', 'dicom_node', 'webhook', 'national_exchange'],

    'fhir' => [
        'version' => '4.0.1',
        'base_path' => '/api/v1/fhir',
        'page_size' => 20,
        'max_page_size' => 100,
        'id_system' => 'urn:healthnexus:id',
        // Resources served, and the scope needed to read them.
        'resources' => ['Patient', 'Practitioner', 'Organization', 'Encounter', 'Appointment', 'Condition', 'Observation', 'AllergyIntolerance', 'Procedure', 'ServiceRequest',
            'DiagnosticReport', 'ImagingStudy', 'MedicationRequest', 'Invoice', 'Coverage'],
        'patient_identifier_systems' => ['mrn' => 'urn:healthnexus:mrn', 'national_id' => 'urn:bd:nid'],
    ],

    // Profiles a partner can be held to. A profile lists the elements that must be present on each resource type it constrains; anything not listed falls back to base R4 rules.
    // `bd-core` is a PLACEHOLDER for the DGHS Bangladesh Core IG until its real profile list is loaded.
    'profiles' => [
        'r4' => ['Patient' => [], 'Encounter' => [], 'Observation' => [], 'DiagnosticReport' => []],
        'bd-core' => [
            'Patient' => ['identifier', 'name', 'gender', 'birthDate'],
            'Encounter' => ['status', 'class', 'subject'],
            'Observation' => ['status', 'code', 'subject'],
            'DiagnosticReport' => ['status', 'code', 'subject'],
            'Condition' => ['clinicalStatus', 'code', 'subject'],
        ],
    ],

    'delivery' => [
        'max_attempts' => 6,
        'backoff_seconds' => [30, 120, 600, 1800, 7200, 21600],
        'timeout_seconds' => 10,
        'batch' => 50,
    ],

    'hl7' => [
        'encoding_chars' => '^~\\&',
        'sending_application' => 'HEALTHNEXUS',
        'sending_facility' => 'HOSPITAL',
        'version' => '2.5.1',
        'listen_host' => '0.0.0.0',
        'listen_port' => 2575,
        'accepted_types' => ['ADT^A01', 'ADT^A04', 'ADT^A08', 'ADT^A03', 'ORU^R01', 'ORM^O01'],
    ],

    'dicom' => [
        'default_station_ae' => 'HNWORKLIST',
        'worklist_days' => 3,
    ],

    'webhook' => [
        'tolerance_seconds' => 300,
        'signature_header' => 'X-HN-Signature',
        'timestamp_header' => 'X-HN-Timestamp',
        'delivery_header' => 'X-HN-Delivery',
    ],

    'credentials' => ['overlap_hours' => 24],

    'tokens' => ['ttl_days' => 90, 'abilities' => ['fhir.read', 'fhir.write', 'dicom.read', 'dicom.write']],

    // Domain events offered to routing rules: key => event class.
    'events' => [
        'patient.registered' => PatientRegistered::class,
        'encounter.completed' => EncounterCompleted::class,
        'appointment.created' => AppointmentCreated::class,
        'invoice.finalized' => InvoiceFinalized::class,
        'lab.report.finalized' => LabReportFinalized::class,
        'prescription.issued' => PrescriptionIssued::class,
    ],

    'national' => ['driver' => env('INTEROP_NATIONAL_DRIVER', 'null')], // null | sandbox (the real adapter waits for DGHS onboarding)

    'retention' => ['processed_payload_days' => 90, 'dead_payload_days' => 365, 'access_log_days' => 730, 'nonce_hours' => 48],

    'review' => ['inbound_result_expires_days' => 30],
];
