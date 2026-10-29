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
        'awaiting_triage' => ['warning', 'bi-clipboard-pulse'], 'in_treatment' => ['primary', 'bi-activity'], 'observation' => ['info', 'bi-eye'],
        'awaiting_disposition' => ['warning', 'bi-signpost-split'],
        // blood bank
        'available' => ['success', 'bi-check-circle'], 'reserved' => ['info', 'bi-bookmark-check'],
        'transfused' => ['secondary', 'bi-droplet-fill'], 'blocked' => ['danger', 'bi-slash-octagon'], 'discarded' => ['secondary', 'bi-trash'],
        'processed' => ['secondary', 'bi-diagram-2'], 'eligible' => ['success', 'bi-person-check'], 'deferred' => ['warning', 'bi-person-dash'], 'permanently_deferred' => ['danger', 'bi-person-x'],
        'collecting' => ['primary', 'bi-droplet-half'], 'aborted' => ['secondary', 'bi-x-circle'], 'sample_received' => ['info', 'bi-eyedropper'],
        'ready' => ['success', 'bi-check2-circle'], 'partially_issued' => ['warning', 'bi-box-arrow-right'], 'provisional' => ['warning', 'bi-hourglass-split'], 'accepted' => ['success', 'bi-check-circle'],
        'transfusing' => ['primary', 'bi-activity'],
        // inventory and procurement
        'qualified' => ['success', 'bi-patch-check'], 'suspended' => ['warning', 'bi-pause-circle'], 'disqualified' => ['danger', 'bi-slash-circle'], 'prospective' => ['secondary', 'bi-hourglass'],
        'matched' => ['success', 'bi-check2-all'], 'mismatch' => ['danger', 'bi-exclamation-diamond'], 'requested' => ['info', 'bi-send'], 'dispatched' => ['primary', 'bi-truck'], 'in_transit' => ['primary', 'bi-truck'],
        'counting' => ['info', 'bi-list-check'], 'partially_received' => ['warning', 'bi-box-seam'], 'pending_inspection' => ['warning', 'bi-search'], 'posted' => ['success', 'bi-box-arrow-in-down'],
        'recorded' => ['info', 'bi-receipt'], 'recalled' => ['danger', 'bi-arrow-return-left'], 'credited' => ['success', 'bi-cash-coin'], 'awarded' => ['success', 'bi-trophy'], 'sent' => ['info', 'bi-send'], 'selected' => ['success', 'bi-check-circle'],
        // people and payroll
        'onboarding' => ['info', 'bi-person-plus'], 'on_leave' => ['info', 'bi-airplane'], 'resigned' => ['secondary', 'bi-box-arrow-right'], 'terminated' => ['danger', 'bi-person-x'], 'retired' => ['secondary', 'bi-person-heart'],
        'late' => ['warning', 'bi-alarm'], 'half_day' => ['warning', 'bi-circle-half'], 'absent' => ['danger', 'bi-person-dash'], 'present' => ['success', 'bi-person-check'], 'weekly_off' => ['secondary', 'bi-moon'], 'holiday' => ['info', 'bi-calendar-heart'],
        'unrostered' => ['secondary', 'bi-question-circle'], 'leave' => ['info', 'bi-airplane'], 'manager_approved' => ['info', 'bi-person-check'], 'calculated' => ['primary', 'bi-calculator'],
        'applied' => ['secondary', 'bi-inbox'], 'screened' => ['info', 'bi-funnel'], 'interview' => ['primary', 'bi-chat-dots'], 'offered' => ['warning', 'bi-envelope-paper'], 'hired' => ['success', 'bi-person-check'], 'withdrawn' => ['secondary', 'bi-person-dash'],
        'attended' => ['success', 'bi-check'], 'passed' => ['success', 'bi-award'], 'repaid' => ['success', 'bi-check-all'], 'done' => ['success', 'bi-check-circle'], 'published' => ['primary', 'bi-megaphone'], 'prepared' => ['info', 'bi-file-earmark-text'],
        'renewed' => ['secondary', 'bi-arrow-repeat'], 'ended' => ['secondary', 'bi-stop-circle'], 'revoked' => ['danger', 'bi-slash-circle'], 'filled' => ['success', 'bi-check-circle'],
        // insurance and corporate claims
        'queried' => ['warning', 'bi-question-circle'], 'denied' => ['danger', 'bi-x-octagon'], 'appealed' => ['primary', 'bi-megaphone'],
        'unallocated' => ['warning', 'bi-hourglass-split'], 'partly_allocated' => ['info', 'bi-cash'], 'allocated' => ['success', 'bi-check-all'],
        'upheld' => ['danger', 'bi-hand-thumbs-down'], 'overturned' => ['success', 'bi-hand-thumbs-up'], 'answered' => ['success', 'bi-reply'], 'settled' => ['success', 'bi-check-all'], 'not_eligible' => ['danger', 'bi-person-x'], 'unknown' => ['secondary', 'bi-question'], 'stopped_reaction' => ['danger', 'bi-exclamation-octagon'], 'investigating' => ['warning', 'bi-search'], 'dispositioned' => ['secondary', 'bi-box-arrow-right'],
        // patient portal and relations
        'invited' => ['info', 'bi-envelope-open'], 'held_critical' => ['danger', 'bi-exclamation-triangle-fill'], 'withheld' => ['warning', 'bi-eye-slash'], 'never' => ['secondary', 'bi-person-lines-fill'],
        'needs_visit' => ['warning', 'bi-calendar-plus'], 'declined' => ['danger', 'bi-x-circle'], 'investigating' => ['primary', 'bi-search'], 'escalated' => ['danger', 'bi-arrow-up-circle'], 'assigned' => ['info', 'bi-person-check'],
        'initiated' => ['info', 'bi-hourglass'], 'succeeded' => ['success', 'bi-check-circle-fill'], 'queued' => ['info', 'bi-inbox'], 'suppressed' => ['secondary', 'bi-slash-circle'], 'new' => ['info', 'bi-star'], 'reviewed' => ['success', 'bi-check2'],
        'running' => ['primary', 'bi-play-circle'], 'unknown_payment' => ['danger', 'bi-question-diamond'], 'amount_differs' => ['danger', 'bi-exclamation-diamond'], 'not_succeeded' => ['warning', 'bi-x-diamond'],
        // finance
        'reconciled' => ['success', 'bi-check-all'], 'banked' => ['success', 'bi-bank'], 'accrued' => ['info', 'bi-hourglass'], 'fully_depreciated' => ['secondary', 'bi-hourglass-bottom'], 'disposed' => ['secondary', 'bi-trash'], 'unmatched' => ['warning', 'bi-question-circle'], 'ignored' => ['secondary', 'bi-eye-slash'],
        // interoperability
        'retrying' => ['warning', 'bi-arrow-repeat'], 'sending' => ['primary', 'bi-send'], 'dead' => ['danger', 'bi-x-octagon'], 'dismissed' => ['secondary', 'bi-archive'],
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
