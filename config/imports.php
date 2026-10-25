<?php

use App\Services\Import\Definitions\BedImport;
use App\Services\Import\Definitions\LabTestImport;
use App\Services\Import\Definitions\MedicationImport;
use App\Services\Import\Definitions\OpeningStockImport;
use App\Services\Import\Definitions\PatientImport;
use App\Services\Import\Definitions\ServiceImport;
use App\Services\Import\Definitions\UserImport;

return [
    'definitions' => [
        PatientImport::class,
        ServiceImport::class,
        MedicationImport::class,
        LabTestImport::class,
        BedImport::class,
        UserImport::class,
        OpeningStockImport::class,
    ],

    'max_rows' => 20000,
    'max_file_kb' => 10240,
    'preview_rows' => 50,
];
