<?php

/*
| Disease watch (Phase 26, B5). The list of conditions and every number below are EXAMPLES for the hospital's infection-control and public-health officers to confirm with the health authority (the civil surgeon's office, IEDCR/DGHS):
| the conditions that must be reported, and the codes and thresholds, are the authority's to decide. Settings (`watch.<key>`) override the general keys; each condition's own numbers are edited on the Conditions screen.
*/
return [
    // How many earlier windows make up "the usual".
    'baseline_windows' => 8,
    // Who is told when a signal is raised, besides the officers who hold the signal right.
    'authorities' => ['Civil Surgeon office', 'IEDCR', 'DGHS control room', 'City corporation health department'],

    // Starting list: code => [name, name in Bangla, ICD-10 prefixes, window days, fewest cases, spike factor, cluster cases, severity].
    'conditions' => [
        'dengue' => ['Dengue', 'ডেঙ্গু', ['A90', 'A91'], 7, 3, 2.0, 3, 'warning'],
        'cholera' => ['Cholera', 'কলেরা', ['A00'], 7, 2, 1.5, 2, 'critical'],
        'typhoid' => ['Typhoid fever', 'টাইফয়েড', ['A01'], 7, 4, 2.0, 4, 'warning'],
        'measles' => ['Measles', 'হাম', ['B05'], 14, 2, 1.5, 2, 'critical'],
        'diphtheria' => ['Diphtheria', 'ডিপথেরিয়া', ['A36'], 14, 1, 1.0, 1, 'critical'],
        'malaria' => ['Malaria', 'ম্যালেরিয়া', ['B50', 'B51', 'B52', 'B53', 'B54'], 14, 2, 2.0, 2, 'warning'],
        'chikungunya' => ['Chikungunya', 'চিকুনগুনিয়া', ['A92.0'], 7, 3, 2.0, 3, 'warning'],
        'hepatitis_e' => ['Hepatitis A or E', 'হেপাটাইটিস এ বা ই', ['B15', 'B17.2'], 14, 3, 2.0, 3, 'warning'],
        'rabies' => ['Rabies', 'জলাতঙ্ক', ['A82'], 30, 1, 1.0, 1, 'critical'],
        'tetanus' => ['Tetanus', 'ধনুষ্টঙ্কার', ['A33', 'A34', 'A35'], 30, 1, 1.0, 1, 'warning'],
    ],
];
