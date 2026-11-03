<?php

/*
| Birth notification, death certification and medico-legal cases (Phase 26, B2). Every number here is an EXAMPLE for the hospital to confirm against the rules it works under: Settings (`vital.<key>`) override it.
| The hospital notifies and certifies; the government registrar (not this system) registers the birth and issues the national certificate.
*/
return [
    'sexes' => ['male', 'female', 'other', 'undetermined'],
    'places' => ['this_hospital', 'en_route', 'home', 'other'],
    'delivery_types' => ['normal', 'caesarean', 'assisted', 'breech', 'other'],
    'outcomes' => ['live_birth', 'stillbirth'],

    'manners' => ['natural', 'accident', 'suicide', 'homicide', 'undetermined', 'pending_investigation'],
    // A death by one of these is a police matter: it is a medico-legal case, and the body is not released until the case is closed.
    'police_manners' => ['accident', 'suicide', 'homicide', 'undetermined', 'pending_investigation'],
    'pregnancy_statuses' => ['not_applicable', 'pregnant', 'within_42_days', '43_days_to_1_year', 'unknown'],
    'id_types' => ['nid', 'birth_certificate', 'passport', 'driving_licence', 'other'],

    'medicolegal_kinds' => ['accident', 'assault', 'burn', 'poisoning', 'sexual_offence', 'suspicious_death', 'brought_dead', 'other'],

    // Hours within which the police should be told of a medico-legal case; a case still open after this is raised on the dashboard.
    'police_inform_hours' => 24,
    // A birth issued a notification but not yet registered with the registrar after this many days is listed for follow-up (an example; the hospital sets the figure).
    'registration_followup_days' => 45,
    // A stillbirth is recorded when the baby is born without signs of life; the gestation from which the hospital counts it is its own rule.
    'stillbirth_from_weeks' => 28,
];
