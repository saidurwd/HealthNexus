<?php

/*
| Time-critical emergency protocols (Phase 12 S08). Every step and every target below is an EXAMPLE to be confirmed and signed off by the
| hospital's clinical champions (OD-12) before clinical use. `target` = minutes from the start of the protocol (null = no timer).
| The hospital can replace this list with its own through the setting ed.protocols.
*/
return [
    'stroke' => ['name' => 'Stroke', 'steps' => [
        ['key' => 'glucose', 'label' => 'Blood glucose checked', 'target' => 10],
        ['key' => 'neuro', 'label' => 'Neurological assessment', 'target' => 10],
        ['key' => 'ct', 'label' => 'Brain scan done', 'target' => 25],
        ['key' => 'ct_read', 'label' => 'Brain scan read by a doctor', 'target' => 45],
        ['key' => 'treatment', 'label' => 'Treatment decision made', 'target' => 60],
    ]],
    'acs' => ['name' => 'Heart attack (ACS)', 'steps' => [
        ['key' => 'ecg', 'label' => 'ECG recorded', 'target' => 10],
        ['key' => 'ecg_read', 'label' => 'ECG read by a doctor', 'target' => 10],
        ['key' => 'aspirin', 'label' => 'Aspirin given (unless not allowed)', 'target' => 15],
        ['key' => 'troponin', 'label' => 'Troponin sent', 'target' => 20],
        ['key' => 'cardiology', 'label' => 'Cardiology informed', 'target' => 30],
    ]],
    'sepsis' => ['name' => 'Sepsis', 'steps' => [
        ['key' => 'lactate', 'label' => 'Lactate measured', 'target' => 30],
        ['key' => 'cultures', 'label' => 'Blood cultures taken', 'target' => 45],
        ['key' => 'antibiotic', 'label' => 'Antibiotic given', 'target' => 60],
        ['key' => 'fluids', 'label' => 'Fluids started', 'target' => 60],
        ['key' => 'reassess', 'label' => 'Reassessed after fluids', 'target' => 180],
    ]],
    'trauma' => ['name' => 'Major trauma', 'steps' => [
        ['key' => 'airway', 'label' => 'Airway and neck protection', 'target' => 5],
        ['key' => 'breathing', 'label' => 'Breathing assessed', 'target' => 5],
        ['key' => 'circulation', 'label' => 'Bleeding controlled and access gained', 'target' => 10],
        ['key' => 'disability', 'label' => 'Consciousness (GCS) recorded', 'target' => 10],
        ['key' => 'exposure', 'label' => 'Fully examined and kept warm', 'target' => 15],
        ['key' => 'imaging', 'label' => 'Imaging done', 'target' => 30],
    ]],

    'body_regions' => ['head', 'face', 'neck', 'chest', 'abdomen', 'pelvis', 'back', 'left_arm', 'right_arm', 'left_leg', 'right_leg'],
    'injury_types' => ['laceration', 'fracture', 'burn', 'bruise', 'penetrating', 'amputation', 'dislocation', 'other'],
    'injury_severities' => ['minor', 'moderate', 'severe', 'critical'],
];
