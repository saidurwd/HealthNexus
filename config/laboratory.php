<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Specimen rejection reasons
    |--------------------------------------------------------------------------
    |
    | Config-driven per Phase 5 spec §22 — never scattered as hardcoded strings across
    | controllers/requests/views. Value => label pairs.
    |
    */
    'rejection_reasons' => [
        'insufficient_quantity' => 'Insufficient quantity',
        'wrong_container' => 'Wrong container',
        'hemolysed' => 'Hemolysed',
        'clotted' => 'Clotted',
        'leaked' => 'Leaked',
        'contaminated' => 'Contaminated',
        'delayed_transport' => 'Delayed transport',
        'incorrect_identification' => 'Incorrect identification',
        'stability_exceeded' => 'Expired / stability exceeded',
        'other' => 'Other',
    ],
];
