<?php

namespace App\Services\Geo;

use App\Models\Geo\GeoDistrict;
use App\Models\Geo\GeoDivision;
use App\Models\Geo\GeoUnion;
use App\Models\Geo\GeoUpazila;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Finding places (in English or Bangla), checking that an address's division, district, upazila and union belong together, adding unions, and importing the national union list. */
class GeoService
{
    public const LEVELS = ['division' => GeoDivision::class, 'district' => GeoDistrict::class, 'upazila' => GeoUpazila::class, 'union' => GeoUnion::class];

    /** Which column of the level holds its parent, and the request parameter that names the parent. */
    public const PARENT = ['district' => 'division_id', 'upazila' => 'district_id', 'union' => 'upazila_id'];

    /** A search word made safe to put inside a LIKE (the query says ESCAPE '!', which MySQL and SQLite both understand). */
    public static function like(string $word): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word).'%';
    }

    /**
     * The places of a level that match what was typed (every word, in English or Bangla), under their parent where the level has one.
     *
     * @return list<GeoDivision|GeoDistrict|GeoUpazila|GeoUnion>
     */
    public function search(string $level, string $query = '', ?int $parentId = null, int $limit = 30): array
    {
        $q = match ($level) {
            'division' => GeoDivision::query(),
            'district' => GeoDistrict::query()->where('division_id', $parentId ?? 0),
            'upazila' => GeoUpazila::query()->where('district_id', $parentId ?? 0),
            'union' => GeoUnion::query()->where('upazila_id', $parentId ?? 0),
            default => abort(404),
        };
        foreach (preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $like = self::like($word);
            $q->where(fn ($w) => $w->whereRaw("name_en like ? escape '!'", [$like])->orWhereRaw("name_bn like ? escape '!'", [$like]));
        }

        return array_values($q->orderBy('name_en')->limit($limit)->get()->all());
    }

    /** The words that stand for a place in a list: the name as the person reads it, with the other language beside it. */
    public function label(GeoDivision|GeoDistrict|GeoUpazila|GeoUnion $place): string
    {
        $other = app()->getLocale() === 'bn' ? $place->name_en : $place->name_bn;

        return $place->displayName().(filled($other) && $other !== $place->displayName() ? ' · '.$other : '');
    }

    /**
     * Checks that the places chosen on an address belong together (a union in the upazila, the upazila in the district, the district in the division), and says which are missing from the top down.
     *
     * @param  array<string,mixed>  $ids  division_id, district_id, upazila_id, union_id (any may be empty)
     * @return array<string,int|null> the four ids, with the parents filled in from the lowest place chosen
     */
    public function resolve(array $ids): array
    {
        $union = $upazila = $district = null;
        if (! empty($ids['union_id'])) {
            $union = GeoUnion::query()->with('upazila.district')->find((int) $ids['union_id']);
            $upazila = $union?->upazila;
        } elseif (! empty($ids['upazila_id'])) {
            $upazila = GeoUpazila::query()->with('district')->find((int) $ids['upazila_id']);
        }
        if ($upazila !== null) {
            $district = $upazila->district;
        } elseif (! empty($ids['district_id'])) {
            $district = GeoDistrict::query()->find((int) $ids['district_id']);
        }
        $divisionId = $district !== null ? $district->division_id : (! empty($ids['division_id']) ? (int) $ids['division_id'] : null);

        $clash = fn (string $key, ?int $found) => ! empty($ids[$key]) && $found !== null && (int) $ids[$key] !== $found;
        if ($clash('upazila_id', $upazila?->id) || $clash('district_id', $district?->id) || $clash('division_id', $divisionId)) {
            throw ValidationException::withMessages(['district_id' => __('The division, district, upazila and union chosen do not belong together.')]);
        }

        return ['division_id' => $divisionId, 'district_id' => $district?->id, 'upazila_id' => $upazila?->id, 'union_id' => $union?->id];
    }

    public function addUnion(int $upazilaId, string $nameEn, ?string $nameBn = null, ?string $code = null, string $source = 'manual'): GeoUnion
    {
        $name = trim($nameEn);
        if ($name === '') {
            throw ValidationException::withMessages(['name_en' => __('Give the name of the union.')]);
        }
        GeoUpazila::query()->findOrFail($upazilaId);

        return GeoUnion::query()->updateOrCreate(['upazila_id' => $upazilaId, 'name_en' => $name], array_filter(['name_bn' => $nameBn !== null ? trim($nameBn) : null, 'code' => $code, 'source' => $source], fn ($v) => $v !== null));
    }

    /**
     * Imports the national union list from a CSV with the heading: division,district,upazila,union_en,union_bn[,code]. A row whose division, district or upazila is not found is reported and skipped, not guessed.
     *
     * @return array{added:int,updated:int,skipped:list<string>}
     */
    public function importUnions(string $path): array
    {
        $handle = @fopen($path, 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => __('The file could not be read.')]);
        }
        $header = fgetcsv($handle, 0, ',', '"', '');
        $header = is_array($header) ? array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h) ?? '')), $header) : [];
        foreach (['division', 'district', 'upazila', 'union_en'] as $need) {
            if (! in_array($need, $header, true)) {
                fclose($handle);
                throw ValidationException::withMessages(['file' => __('The first line must name the columns: division, district, upazila, union_en, union_bn.')]);
            }
        }
        $out = ['added' => 0, 'updated' => 0, 'skipped' => []];
        $line = 1;
        DB::transaction(function () use ($handle, $header, &$out, &$line) {
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;
                if ($row === [null] || count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                    continue;
                }
                $r = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), ''));
                $upazila = GeoUpazila::query()->whereRaw('lower(name_en) = ?', [mb_strtolower(trim($r['upazila']))])
                    ->whereHas('district', fn ($d) => $d->whereRaw('lower(name_en) = ?', [mb_strtolower(trim($r['district']))])->whereHas('division', fn ($v) => $v->whereRaw('lower(name_en) = ?', [mb_strtolower(trim($r['division']))])))->first();
                if ($upazila === null || trim($r['union_en']) === '') {
                    $out['skipped'][] = __('Line :n: :what', ['n' => $line, 'what' => $upazila === null ? __('the upazila was not found') : __('the union has no name')]);

                    continue;
                }
                $union = $this->addUnion($upazila->id, $r['union_en'], $r['union_bn'] ?? null, ($r['code'] ?? '') !== '' ? $r['code'] : null, 'import');
                $union->wasRecentlyCreated ? $out['added']++ : $out['updated']++;
            }
        });
        fclose($handle);

        return $out;
    }

    /**
     * Links older addresses, which only have the district as typed text, to the district it names (English or Bangla, any capitals). Addresses already linked, and text that matches no district, are left alone.
     *
     * @return array{linked:int,unmatched:int}
     */
    public function linkTypedDistricts(): array
    {
        $districts = GeoDistrict::query()->get();
        $byName = [];
        foreach ($districts as $d) {
            $byName[mb_strtolower($d->name_en)] = $d;
            $byName[$d->name_bn] = $d;
        }
        $linked = $unmatched = 0;
        DB::table('patient_addresses')->whereNull('district_id')->whereNotNull('district')->where('district', '!=', '')->orderBy('id')->select(['id', 'district'])->chunkById(500, function ($rows) use ($byName, &$linked, &$unmatched) {
            foreach ($rows as $row) {
                $d = $byName[mb_strtolower(trim($row->district))] ?? $byName[trim($row->district)] ?? null;
                if ($d === null) {
                    $unmatched++;

                    continue;
                }
                DB::table('patient_addresses')->where('id', $row->id)->update(['district_id' => $d->id, 'division_id' => $d->division_id]);
                $linked++;
            }
        });

        return ['linked' => $linked, 'unmatched' => $unmatched];
    }
}
