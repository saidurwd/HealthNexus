<?php

return [
    'definitions' => [
        \App\Services\Import\Definitions\PatientImport::class,
        \App\Services\Import\Definitions\ServiceImport::class,
        \App\Services\Import\Definitions\MedicationImport::class,
        \App\Services\Import\Definitions\LabTestImport::class,
        \App\Services\Import\Definitions\BedImport::class,
        \App\Services\Import\Definitions\UserImport::class,
        \App\Services\Import\Definitions\OpeningStockImport::class,
    ],

    'max_rows' => 20000,
    'max_file_kb' => 10240,
    'preview_rows' => 50,
];
