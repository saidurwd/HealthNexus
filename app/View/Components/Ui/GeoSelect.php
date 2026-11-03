<?php

namespace App\View\Components\Ui;

use App\Models\Geo\GeoDistrict;
use App\Models\Geo\GeoDivision;
use App\Models\Geo\GeoUnion;
use App\Models\Geo\GeoUpazila;
use App\Services\Geo\GeoService;
use Illuminate\Contracts\View\View;

/** A searchable dropdown for one level of the country's geography (division, district, upazila, union). A level below the first waits for the one above it: give that one with depends-on. */
class GeoSelect extends LookupSelect
{
    public function __construct(public string $level, string $name, mixed $value = null, bool $required = false, ?string $dependsOn = null, ?string $placeholder = null)
    {
        parent::__construct($name, $value, $required, $dependsOn, $placeholder);
    }

    protected function route(): string
    {
        return 'admin.lookup.geo';
    }

    protected function defaultPlaceholder(): string
    {
        $levels = ['division' => __('Choose the division'), 'district' => __('Choose the division first, then the district'), 'upazila' => __('Choose the district first, then the upazila'), 'union' => __('Choose the upazila first, then the union')];

        return $levels[$this->level] ?? __('Choose');
    }

    protected function label(int $id, int $organizationId): ?string
    {
        $class = GeoService::LEVELS[$this->level] ?? null;
        /** @var GeoDivision|GeoDistrict|GeoUpazila|GeoUnion|null $place */
        $place = $class ? $class::query()->find($id) : null;

        return $place ? app(GeoService::class)->label($place) : null;
    }

    public function render(): View
    {
        return view('components.ui.lookup-select', ['url' => route($this->route(), $this->level), 'hint' => $this->placeholder ?? $this->defaultPlaceholder()]);
    }
}
