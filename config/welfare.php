<?php

/*
| Patient welfare (Phase 26). Every number here is an EXAMPLE for the hospital's welfare committee to set: Settings (`welfare.<key>`) override it. Nothing is a legal or government poverty line.
*/
return [
    // Kinds of money that can fill a fund.
    'fund_kinds' => ['zakat', 'donation', 'government', 'csr', 'hospital'],
    'donor_kinds' => ['individual', 'company', 'organisation'],

    // Means test: income per person in the household per month (BDT), plus points for circumstances. The result is a SUGGESTION to the committee, never a decision.
    'means' => [
        'free_below_per_head' => 3000,
        'subsidised_below_per_head' => 6000,
        'free_percent' => 100,
        'subsidised_percent' => 50,
        // Points added for these circumstances; the points can move a person up one category, never two.
        'points_to_upgrade' => 2,
    ],

    // Circumstances the assessor can tick (key => points). Words are shown through the translation files.
    'factors' => [
        'no_earner' => 2, 'widow_headed' => 1, 'disabled_member' => 1, 'elderly_alone' => 1, 'orphan' => 1, 'long_illness' => 1, 'card_holder' => 1,
    ],

    // How long an approved assessment and a grant stay valid, in days.
    'assessment_valid_days' => 180,
    'grant_valid_days' => 90,

    // A request waiting longer than this many days is raised to the people who can act on it.
    'waiting_days' => 3,

    // A waiver of more than this share of one bill needs the amount to be typed again by the person who approves the grant.
    'warn_percent_of_bill' => 100,
];
