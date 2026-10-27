<?php

/*
| Inventory / Procurement defaults (Phase 15). EVERY value here is an EXAMPLE for the stores and procurement managers to confirm (OD-15);
| the hospital's real values are set in Settings (`inventory.<key>`) and override these. Nothing supplier- or hospital-specific is hard-coded.
*/
return [
    'category_kinds' => ['consumable', 'surgical', 'linen', 'stationery', 'housekeeping', 'spare', 'reagent', 'equipment'],

    'store_kinds' => ['central', 'sub', 'ward', 'ot', 'housekeeping', 'quarantine'],

    // Units of measure offered on screens; an item can still define its own conversion (box = 10 pieces).
    'uoms' => ['each', 'piece', 'box', 'pack', 'carton', 'bottle', 'roll', 'pair', 'set', 'kg', 'litre', 'metre'],

    'issue_rules' => ['fefo', 'fifo'],

    // Supplier qualification (S02)
    'supplier_document_types' => ['trade_licence', 'drug_licence', 'vat_bin', 'tax_certificate', 'other'],
    'supplier_required_documents' => ['trade_licence', 'vat_bin'],
    'document_expiry_alert_days' => [60, 30, 7],
    // Weights of the supplier scorecard (they add up to 1); a score below `supplier_min_score` (out of 5) suspends the supplier.
    'evaluation_weights' => ['delivery' => 0.4, 'quality' => 0.4, 'price' => 0.2],
    'supplier_min_score' => 2.5,

    // Approval by amount (S04, S06): every level whose `from` is at or below the document total must approve, each by a different person.
    'approval_levels' => [
        ['level' => 1, 'from' => 0, 'permission' => 'procurement.approval.level1', 'label' => 'Store or department head'],
        ['level' => 2, 'from' => 50000, 'permission' => 'procurement.approval.level2', 'label' => 'Procurement manager'],
        ['level' => 3, 'from' => 500000, 'permission' => 'procurement.approval.level3', 'label' => 'Director'],
    ],

    // Emergency procurement (S11): allowed up to this amount, ratified within this many hours, never a bypass of receiving or matching.
    'emergency_max_amount' => 200000,
    'emergency_ratify_hours' => 48,

    // Three-way match (S10): a difference inside the tolerance still matches; outside it, the invoice waits for a second person.
    'match_tolerance' => ['price_percent' => 2.0, 'quantity_percent' => 0.0, 'amount' => 50.0],

    // A PO line can be over-received by this share of the ordered quantity (0 = never).
    'over_receipt_percent' => 0,

    // Expiry (S09)
    'expiry_alert_days' => [90, 30],
    'min_remaining_shelf_life_days' => 30, // goods delivered with less left than this are rejected on receipt

    // Reorder suggestions (S11)
    'reorder' => ['lookback_days' => 90, 'safety_days' => 7],

    // Count variance: a variance never posts by itself; this share of the system quantity (or more) also needs the store manager's note.
    'count_variance_note_percent' => 5.0,

    // Adjustments
    'adjustment_reason_codes' => ['damage', 'expired', 'theft', 'count_variance', 'found', 'other'],

    // Purchase order state machine: from => allowed next.
    'po_transitions' => [
        'draft' => ['pending_approval', 'cancelled'],
        'pending_approval' => ['approved', 'draft', 'cancelled', 'issued', 'partially_received'], // the last two: an issued order sent back for more approval after an amendment
        'approved' => ['issued', 'cancelled'],
        'issued' => ['partially_received', 'received', 'closed', 'cancelled'],
        'partially_received' => ['received', 'closed'],
        'received' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ],
];
