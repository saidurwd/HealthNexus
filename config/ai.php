<?php

/*
| AI and clinical intelligence (Phase 20). Everything here is a starting point for the hospital's AI governance committee: Settings `ai.<key>` win over this file.
| Nothing is sent to any outside service unless a provider is configured, approved and enabled. API keys are never stored here or in the database: a provider names
| an environment variable (`secret_env`) that is read at call time.
*/
return [
    // The master kill switch. Off = every AI call is refused.
    'enabled' => true,

    'timezone' => 'Asia/Dhaka',

    'risk_classes' => ['low', 'medium', 'high'],

    // C1 public, C2 internal, C3 confidential, C4 identifiable health data.
    'data_levels' => ['C1', 'C2', 'C3', 'C4'],

    'provider' => [
        'kinds' => ['fake', 'rules', 'http'],
        'locations' => ['on_premise', 'bangladesh', 'international'],
        // Providers in these places receive de-identified context only, unless a legal approval is recorded.
        'external_locations' => ['international'],
        'timeout_seconds' => 20,
    ],

    'context' => [
        'max_items' => 80,
        'max_chars' => 12000,
        'timeline_days' => 365,
        'max_text_chars' => 500,
    ],

    'output' => [
        'label' => 'AI-generated — needs review',
        'expire_hours' => 72,
    ],

    // The words that make a line an instruction to act. A line containing one is dropped from any output (English and Bangla).
    'forbidden_actions' => [
        'prescribe', 'start ', 'stop ', 'increase the dose', 'decrease the dose', 'change the dose', 'order a', 'order an', 'discharge the', 'discharge him', 'discharge her', 'transfer the', 'transfer to', 'admit the', 'admit to', 'diagnose', 'diagnosis is',
        'give the patient', 'administer', 'cancel the', 'override', 'approve the claim', 'bill the', 'inject', 'should be given', 'must be given', 'withhold',
        'প্রেসক্রাইব', 'ওষুধ দিন', 'ডোজ বাড়ান', 'ডোজ কমান', 'ভর্তি করুন', 'ছাড়পত্র দিন', 'স্থানান্তর করুন', 'অর্ডার করুন', 'ইনজেকশন দিন', 'বন্ধ করুন', 'শুরু করুন',
    ],

    // Text that looks like an instruction to the model. Free text containing these is flagged and quoted as data.
    'injection_patterns' => [
        'ignore (all |any |the )?(previous|prior|above|earlier) (instructions|rules|prompts)', 'disregard (all |any |the )?(previous|prior|above|earlier)', 'forget (all |your |the )?(previous|prior)?\s*(instructions|rules)',
        'you are now', 'act as', 'pretend (to be|you are)', 'system prompt', 'developer message', 'new instructions', 'reveal (your|the) (prompt|instructions|rules)', 'override (the )?(rules|policy|safety)',
        'jailbreak', 'do anything now', '<\s*/?\s*(system|assistant|tool)\s*>', '\[\s*(system|inst)\s*\]', 'function_call', 'tool_call', '"role"\s*:\s*"system"',
        'আগের (সব )?নির্দেশ(না)? (উপেক্ষা|ভুলে)', 'নির্দেশনা উপেক্ষা', 'সিস্টেম প্রম্পট', 'নতুন নির্দেশ', 'তুমি এখন', 'নিয়ম (উপেক্ষা|ভঙ্গ) কর',
    ],

    // A request whose free text scores this many injection hits is refused for medium- and high-risk use cases (low-risk ones continue with the text quoted as data).
    'injection_block_score' => 3,

    'evaluation' => [
        'min_recall' => 0.9,
        'max_unsupported' => 0,
        'min_cases' => 5,
        'languages_required' => ['en', 'bn'],
    ],

    'monitoring' => [
        'window_days' => 30,
        // Acceptance this many points lower than the period before raises a drift incident.
        'drift_points' => 20,
        'min_outputs' => 10,
    ],

    'knowledge' => [
        'min_score' => 0.3,
        'min_matches' => 2,
        'max_passages' => 3,
    ],

    'forecast' => [
        'history_days' => 90,
        'horizon_days' => 14,
    ],

    'secrets' => [
        // The name of the environment variable holds the key; a provider row stores only the name.
    ],
];
