<?php

/*
| Patient portal, mobile API and CRM defaults (Phase 17). EVERY number here is an EXAMPLE to be confirmed by management, the privacy officer and a lawyer (OD-17).
| The hospital's values are set in Settings (`portal.<key>`); these only fill an empty system.
*/
return [
    'account_statuses' => ['invited', 'pending', 'active', 'locked', 'suspended', 'closed'],

    'otp' => [
        'length' => 6,
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        'resend_after_seconds' => 60,
        'max_per_phone_per_hour' => 5,
        'max_per_ip_per_hour' => 20,
    ],

    'login' => [
        'max_failures' => 5,
        'lock_minutes' => 15,
        'new_device_needs_otp' => true,
        'password_min_length' => 8,
    ],

    // A match needs the phone number on the patient record AND the date of birth AND the patient number or the national id.
    'registration' => [
        'invitation_ttl_days' => 14,
        'minimum_age_years' => 16,
    ],

    'consent_types' => ['data_sharing', 'photography', 'research', 'clinical_marketing'],

    'terms_version' => '1',

    'viewer_url' => env('PORTAL_VIEWER_URL', ''), // e.g. https://pacs.example.org/viewer?accession={accession}

    'tokens' => ['ttl_days' => 30, 'ability' => 'portal'],

    'api' => ['version' => 'v1', 'min_app_version' => '1.0.0', 'page_size' => 20, 'max_page_size' => 50],

    // Result release (guideline override 2). Mode per category: immediate | after_review | delayed | never.
    'release' => [
        'default_mode' => 'after_review',
        'default_delay_hours' => 24,
        'sync_window_days' => 30,
        'modes' => ['immediate', 'after_review', 'delayed', 'never'],
        // Words in a test category or name that make it sensitive: always after_review at least.
        'sensitive_words' => ['hiv', 'pregnan', 'genetic', 'biopsy', 'histopath', 'cytology', 'oncolog', 'hepatitis', 'std', 'sti', 'drug screen'],
    ],

    'documents' => ['link_ttl_days' => 7, 'max_downloads' => 5],

    'payments' => [
        'methods' => ['bkash', 'nagad', 'card'],
        'currency' => 'BDT',
        'minimum' => '10.00',
        'pending_expires_minutes' => 30,
        'driver' => env('PORTAL_PAYMENT_DRIVER', 'null'), // null | sandbox (a real adapter is chosen when the hospital picks an aggregator)
        'method_code' => 'online', // the billing_payment_methods.code that online payments are posted to
    ],

    'messaging' => [
        'categories' => ['general', 'appointment', 'billing', 'results', 'prescription', 'other'],
        'urgent_words' => ['chest pain', 'can\'t breathe', 'cannot breathe', 'difficulty breathing', 'unconscious', 'bleeding', 'stroke', 'seizure', 'suicide', 'overdose', 'poison', 'severe pain', 'heart attack',
            'বুকে ব্যথা', 'শ্বাস নিতে পারছি না', 'অজ্ঞান', 'রক্তক্ষরণ', 'খিঁচুনি', 'আত্মহত্যা'],
        'emergency_number' => '999',
        'reply_due_hours' => 48,
    ],

    'service_requests' => [
        'kinds' => ['records_copy', 'report_reprint', 'appointment_help', 'address_change', 'name_correction', 'insurance_update', 'other'],
        'due_days' => 5,
    ],

    'appointments' => ['horizon_days' => 60, 'min_lead_hours' => 2, 'change_cutoff_hours' => 4, 'max_open' => 6],

    'refill' => ['window_days' => 90, 'min_days_between' => 7],

    'proxy' => [
        'relationships' => ['parent', 'guardian', 'spouse', 'child', 'sibling', 'caregiver', 'other'],
        'scopes' => ['appointments', 'results', 'billing', 'prescriptions', 'documents', 'messages'],
        'minor_age_years' => 18,
        'default_valid_months' => 12,
        // A teenager's own account hides these from a parent's proxy even if scoped (privacy of an adolescent).
        'adolescent_age_from' => 13,
        'adolescent_hidden_scopes' => ['results', 'messages'],
    ],

    // Channel x purpose. transactional cannot be switched off; marketing needs an explicit opt-in.
    'communication' => [
        'channels' => ['sms', 'email', 'push', 'inapp'],
        'purposes' => ['transactional', 'service', 'marketing'],
        'quiet_hours' => ['start' => '21:00', 'end' => '08:00'],
        'timezone' => 'Asia/Dhaka',
        'max_marketing_per_week' => 2,
    ],

    'crm' => [
        'interaction_channels' => ['call', 'walk_in', 'sms', 'email', 'portal', 'social', 'web'],
        'complaint_categories' => ['clinical_care', 'staff_behaviour', 'waiting_time', 'billing', 'cleanliness', 'facility', 'privacy', 'other'],
        'complaint_due_hours' => ['low' => 120, 'medium' => 72, 'high' => 24],
        'feedback_window_days' => 14,
        'segment_criteria' => ['age_from', 'age_to', 'sex', 'last_visit_before_days', 'last_visit_within_days', 'has_portal', 'branch_id', 'language', 'visits_at_least', 'problem_code_prefix'],
    ],

    'retention' => ['otp_days' => 2, 'security_event_days' => 365, 'access_log_days' => 730, 'closed_account_days' => 90],
];
