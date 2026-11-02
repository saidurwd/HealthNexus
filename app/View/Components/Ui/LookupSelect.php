<?php

namespace App\View\Components\Ui;

use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A dropdown that finds a record by searching as the person types (patients, visits, admissions) instead of asking them to remember an internal ID. The record already chosen (an earlier answer, or one passed in) is
 * shown by its name; everything else is looked up from the server (see public/js/searchable-select.js, data-remote). The value sent is the ID, so the screen's controller is unchanged.
 */
abstract class LookupSelect extends Component
{
    public ?string $selected;

    public ?string $selectedLabel = null;

    public function __construct(public string $name, mixed $value = null, public bool $required = false, public ?string $dependsOn = null, public ?string $placeholder = null)
    {
        $this->selected = old($this->name, $value) !== null && old($this->name, $value) !== '' ? (string) old($this->name, $value) : null;
        $this->selected !== null && $this->selectedLabel = $this->label((int) $this->selected, (int) app(TenantContextResolver::class)->getOrganizationId());
        $this->selectedLabel === null && $this->selected = null;
    }

    abstract protected function route(): string;

    abstract protected function defaultPlaceholder(): string;

    /** The words that stand for this record in the list; null when it is not in this hospital. */
    abstract protected function label(int $id, int $organizationId): ?string;

    protected function date(mixed $v): string
    {
        return CarbonImmutable::parse((string) $v, 'UTC')->setTimezone((string) config('ux.timezone', 'Asia/Dhaka'))->format('d M Y');
    }

    public function render(): View
    {
        return view('components.ui.lookup-select', ['url' => route($this->route()), 'hint' => $this->placeholder ?? $this->defaultPlaceholder()]);
    }
}
