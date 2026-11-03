<?php

namespace App\Services\Geo;

use App\Models\Geo\GeoDistrict;
use App\Models\Geo\GeoDivision;
use App\Models\Geo\GeoUpazila;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Puts the country's divisions, districts and upazilas in the database from database/data/bd-geography.json. Safe to run again: a place is found by its English name under its parent and updated, never duplicated. */
class GeoInstaller
{
    /** @return array{divisions:int,districts:int,upazilas:int,created:int} how many places the file holds, and how many were new */
    public function install(?string $path = null): array
    {
        $path ??= database_path('data/bd-geography.json');
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (! is_array($data) || ! isset($data['divisions']) || ! is_array($data['divisions'])) {
            throw new RuntimeException("The geography file [$path] is missing or not valid.");
        }
        $count = ['divisions' => 0, 'districts' => 0, 'upazilas' => 0, 'created' => 0];

        DB::transaction(function () use ($data, &$count) {
            foreach ($data['divisions'] as $dv) {
                $division = GeoDivision::query()->updateOrCreate(['name_en' => trim($dv['name_en'])], ['name_bn' => trim($dv['name_bn'])]);
                $count['divisions']++;
                $division->wasRecentlyCreated && $count['created']++;
                foreach ($dv['districts'] as $ds) {
                    $district = GeoDistrict::query()->updateOrCreate(['division_id' => $division->id, 'name_en' => trim($ds['name_en'])], ['name_bn' => trim($ds['name_bn'])]);
                    $count['districts']++;
                    $district->wasRecentlyCreated && $count['created']++;
                    foreach ($ds['upazilas'] as $up) {
                        $upazila = GeoUpazila::query()->updateOrCreate(['district_id' => $district->id, 'name_en' => trim($up['name_en'])], ['name_bn' => trim($up['name_bn'])]);
                        $count['upazilas']++;
                        $upazila->wasRecentlyCreated && $count['created']++;
                    }
                }
            }
        });

        return $count;
    }
}
