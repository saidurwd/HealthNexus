<?php

use App\Services\Documents\Providers\PrescriptionDocument;
use App\Services\Documents\Providers\ReceiptDocument;
use App\Services\Documents\Providers\WristbandDocument;

return [
    'timezone' => env('DOCUMENT_TIMEZONE', 'Asia/Dhaka'),

    'providers' => [
        ReceiptDocument::class,
        PrescriptionDocument::class,
        WristbandDocument::class,
    ],

    'languages' => ['en', 'bn', 'en+bn'],

    // Page rules per paper type. Output is HTML with @page rules: the browser prints it (Bangla shaped by the
    // browser). A server-side PDF engine is a separate decision (guideline 5, "Test Bangla conjuncts first").
    'papers' => [
        'thermal80' => ['page' => '80mm auto', 'width' => '72mm', 'font' => '11px'],
        'a4' => ['page' => 'A4', 'width' => '190mm', 'font' => '13px'],
        'a5' => ['page' => 'A5', 'width' => '128mm', 'font' => '12px'],
        'label' => ['page' => '90mm 30mm', 'width' => '86mm', 'font' => '11px'],
    ],

    'font_stack' => "'Noto Sans Bengali', 'Kohinoor Bangla', 'Bangla Sangam MN', 'Nirmala UI', 'SolaimanLipi', sans-serif",
];
