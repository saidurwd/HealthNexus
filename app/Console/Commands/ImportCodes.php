<?php

namespace App\Console\Commands;

use App\Models\Terminology\CodeSystem;
use App\Services\Terminology\CodeImporter;
use Illuminate\Console\Command;

class ImportCodes extends Command
{
    protected $signature = 'codes:import {system : Code system key (icd10, icd11, loinc, atc, ucum, snomed, dgda)}
        {file : CSV converted from the official distribution}
        {--delimiter=, : Field delimiter}
        {--code-col=code} {--display-col=display_en} {--display-bn-col=display_bn} {--parent-col=parent_code}
        {--release= : Release version of the file}
        {--map-to= : Treat the file as a map (from_code,to_code[,relationship]) from {system} to this system key}';

    protected $description = 'Load concepts (or code maps) from an official code-system file into the Code System Registry';

    public function handle(CodeImporter $importer): int
    {
        $system = CodeSystem::where('key', $this->argument('system'))->first();

        if (! $system) {
            $this->error('Unknown code system. Run `php artisan db:seed --class=CodeSystemSeeder` first.');

            return self::FAILURE;
        }

        try {
            if ($target = $this->option('map-to')) {
                $to = CodeSystem::where('key', $target)->firstOrFail();
                $result = $importer->importMaps($system, $to, $this->argument('file'), $this->option('delimiter'));
            } else {
                $result = $importer->importConcepts($system, $this->argument('file'), [
                    'code' => $this->option('code-col'),
                    'display_en' => $this->option('display-col'),
                    'display_bn' => $this->option('display-bn-col'),
                    'parent_code' => $this->option('parent-col'),
                ], $this->option('delimiter'), $this->option('release'));
            }
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Imported {$result['rows']} rows, skipped {$result['skipped']}.");

        return self::SUCCESS;
    }
}
