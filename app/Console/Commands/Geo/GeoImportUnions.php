<?php

namespace App\Console\Commands\Geo;

use App\Services\Geo\GeoService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class GeoImportUnions extends Command
{
    protected $signature = 'geo:import-unions {file : A CSV with the columns division,district,upazila,union_en,union_bn[,code]}';

    protected $description = 'Import the national list of unions from a CSV file.';

    public function handle(GeoService $geo): int
    {
        try {
            $r = $geo->importUnions((string) $this->argument('file'));
        } catch (ValidationException $e) {
            $this->error(implode(' ', array_merge(...array_values($e->errors()))));

            return self::INVALID;
        }
        $this->info("{$r['added']} unions added, {$r['updated']} updated, ".count($r['skipped']).' skipped.');
        foreach ($r['skipped'] as $why) {
            $this->line('  '.$why);
        }

        return self::SUCCESS;
    }
}
