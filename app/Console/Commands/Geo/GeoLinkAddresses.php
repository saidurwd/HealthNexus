<?php

namespace App\Console\Commands\Geo;

use App\Services\Geo\GeoService;
use Illuminate\Console\Command;

class GeoLinkAddresses extends Command
{
    protected $signature = 'geo:link-addresses';

    protected $description = 'Link older patient addresses, which only have the district typed as text, to the district it names.';

    public function handle(GeoService $geo): int
    {
        $r = $geo->linkTypedDistricts();
        $this->info("{$r['linked']} addresses linked to a district, {$r['unmatched']} typed districts matched none.");

        return self::SUCCESS;
    }
}
