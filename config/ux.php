<?php

/*
| UX standard (guideline 19, H-14).
|
| `statuses`: ONE palette for the whole product. A status is always shown as colour + icon + text, never colour alone (rule 6).
| Variants are Bootstrap contextual colours. A status not listed here is shown neutral with its own name, so a new module never breaks a screen.
|
| `role_homes`: where each role lands after login (rule 2). First role the user holds that is listed wins; the target must exist, its
| module must be on, and the user must hold the permission — otherwise the next role, then the dashboard.
*/
return [
    'statuses' => [
        // shared vocabulary (guideline 9.4)
        'active' => ['success', 'bi-check-circle'], 'inactive' => ['secondary', 'bi-dash-circle'], 'archived' => ['secondary', 'bi-archive'],
        'draft' => ['secondary', 'bi-pencil'], 'submitted' => ['info', 'bi-send'], 'pending_approval' => ['warning', 'bi-hourglass-split'],
        'approved' => ['success', 'bi-check2-circle'], 'rejected' => ['danger', 'bi-x-circle'], 'returned' => ['warning', 'bi-arrow-return-left'],
        'cancelled' => ['secondary', 'bi-slash-circle'], 'completed' => ['success', 'bi-check-all'], 'closed' => ['secondary', 'bi-lock'],
        'ordered' => ['info', 'bi-cart'], 'accepted' => ['info', 'bi-hand-thumbs-up'], 'in_progress' => ['primary', 'bi-arrow-repeat'],
        'entered' => ['info', 'bi-keyboard'], 'verified' => ['success', 'bi-patch-check'], 'final' => ['success', 'bi-check-circle-fill'],
        'amended' => ['warning', 'bi-pencil-square'], 'entered_in_error' => ['danger', 'bi-exclamation-octagon'],
        'finalized' => ['success', 'bi-check-circle-fill'], 'partially_paid' => ['warning', 'bi-pie-chart'], 'paid' => ['success', 'bi-cash-coin'],
        'written_off' => ['secondary', 'bi-eraser'], 'reversed' => ['secondary', 'bi-arrow-counterclockwise'],
        // queues and visits
        'waiting' => ['warning', 'bi-clock'], 'called' => ['info', 'bi-megaphone'], 'scheduled' => ['info', 'bi-calendar-event'],
        'confirmed' => ['primary', 'bi-calendar-check'], 'checked_in' => ['info', 'bi-box-arrow-in-right'], 'no_show' => ['danger', 'bi-person-x'],
        'pending' => ['warning', 'bi-hourglass'], 'open' => ['info', 'bi-unlock'], 'paused' => ['warning', 'bi-pause-circle'], 'locked' => ['secondary', 'bi-lock-fill'],
        'skipped' => ['secondary', 'bi-skip-forward'], 'started' => ['primary', 'bi-play-circle'],
        // beds and admissions
        'available' => ['success', 'bi-check-circle'], 'occupied' => ['danger', 'bi-person-fill'], 'reserved' => ['info', 'bi-bookmark'],
        'blocked' => ['dark', 'bi-sign-stop'], 'cleaning' => ['warning', 'bi-droplet'], 'maintenance' => ['secondary', 'bi-tools'],
        'admitted' => ['primary', 'bi-hospital'], 'discharged' => ['secondary', 'bi-box-arrow-right'],
        // clinical safety
        'critical' => ['danger', 'bi-exclamation-triangle-fill'], 'warning' => ['warning', 'bi-exclamation-triangle'], 'info' => ['info', 'bi-info-circle'],
        'urgent' => ['danger', 'bi-lightning-charge'], 'stat' => ['danger', 'bi-lightning-charge-fill'], 'routine' => ['secondary', 'bi-circle'],
        'acknowledged' => ['success', 'bi-check2'], 'resolved' => ['success', 'bi-check2-circle'], 'overridden' => ['warning', 'bi-shield-exclamation'],
        'high' => ['danger', 'bi-arrow-up-circle'], 'moderate' => ['warning', 'bi-dash-circle'], 'low' => ['info', 'bi-arrow-down-circle'],
        'mild' => ['info', 'bi-circle'], 'severe' => ['danger', 'bi-exclamation-circle'],
        // lab / pharmacy / billing specifics
        'collected' => ['info', 'bi-droplet-half'], 'received' => ['info', 'bi-inbox'], 'resulted' => ['primary', 'bi-clipboard-data'],
        'dispensed' => ['success', 'bi-capsule'], 'expired' => ['danger', 'bi-calendar-x'], 'quarantined' => ['warning', 'bi-shield-lock'],
        'issued' => ['success', 'bi-receipt'], 'voided' => ['secondary', 'bi-x-octagon'], 'refunded' => ['info', 'bi-arrow-counterclockwise'],
        'deceased' => ['dark', 'bi-heart-pulse'], 'success' => ['success', 'bi-check-circle'], 'failed' => ['danger', 'bi-x-circle'],
    ],

    'role_homes' => [
        'receptionist' => ['route' => 'admin.queue.index', 'permission' => 'queue.view'],
        'doctor' => ['route' => 'admin.queue.index', 'permission' => 'queue.view'],
        'staff_nurse' => ['route' => 'admin.nursing.episodes.index', 'permission' => 'nursing.dashboard.view', 'module' => 'nursing'],
        'senior_staff_nurse' => ['route' => 'admin.nursing.episodes.index', 'permission' => 'nursing.dashboard.view', 'module' => 'nursing'],
        'charge_nurse' => ['route' => 'admin.nursing.dashboard', 'permission' => 'nursing.dashboard.view', 'module' => 'nursing'],
        'nurse' => ['route' => 'admin.nursing.episodes.index', 'permission' => 'nursing.dashboard.view', 'module' => 'nursing'],
        'nursing_supervisor' => ['route' => 'admin.nursing.dashboard', 'permission' => 'nursing.dashboard.view', 'module' => 'nursing'],
        'pharmacist' => ['route' => 'admin.pharmacy.orders.index', 'permission' => 'pharmacy.dashboard.view', 'module' => 'pharmacy'],
        'senior_pharmacist' => ['route' => 'admin.pharmacy.orders.index', 'permission' => 'pharmacy.dashboard.view', 'module' => 'pharmacy'],
        'pharmacy_technician' => ['route' => 'admin.pharmacy.orders.index', 'permission' => 'pharmacy.dashboard.view', 'module' => 'pharmacy'],
        'cashier' => ['route' => 'admin.billing.cashier.index', 'permission' => 'billing.dashboard.view'],
        'lab_technician' => ['route' => 'admin.lab.worklist.index', 'permission' => 'lab.dashboard.view', 'module' => 'lab'],
        'senior_lab_technician' => ['route' => 'admin.lab.worklist.index', 'permission' => 'lab.dashboard.view', 'module' => 'lab'],
        'phlebotomist' => ['route' => 'admin.lab.worklist.index', 'permission' => 'lab.dashboard.view', 'module' => 'lab'],
        'pathologist' => ['route' => 'admin.lab.worklist.index', 'permission' => 'lab.dashboard.view', 'module' => 'lab'],
        'radiology_technician' => ['route' => 'admin.radiology.dashboard', 'permission' => 'radiology.dashboard.view', 'module' => 'radiology'],
        'radiologist' => ['route' => 'admin.radiology.dashboard', 'permission' => 'radiology.dashboard.view', 'module' => 'radiology'],
        'admission_officer' => ['route' => 'admin.ipd.dashboard', 'permission' => 'ipd.dashboard.view', 'module' => 'ipd'],
        'ward_manager' => ['route' => 'admin.ipd.dashboard', 'permission' => 'ipd.dashboard.view', 'module' => 'ipd'],
        'surgeon' => ['route' => 'admin.ot.dashboard', 'permission' => 'ot.dashboard.view', 'module' => 'ot'],
        'ot_nurse' => ['route' => 'admin.ot.dashboard', 'permission' => 'ot.dashboard.view', 'module' => 'ot'],
        'ot_coordinator' => ['route' => 'admin.ot.dashboard', 'permission' => 'ot.dashboard.view', 'module' => 'ot'],
    ],
];
