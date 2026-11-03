<?php

namespace App\View\Components\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/** Colour + icon + text, from the one product palette in config/ux.php. Unknown statuses render neutral with their own name. */
class StatusBadge extends Component
{
    public string $variant;

    public string $icon;

    public string $text;

    public function __construct(public ?string $status, ?string $label = null)
    {
        $key = Str::of((string) $status)->lower()->replace([' ', '-'], '_')->toString();
        [$this->variant, $this->icon] = config("ux.statuses.$key") ?? ['secondary', 'bi-circle'];

        $this->text = $label ?? (string) (Str::headline($key) === '' ? '—' : __(Str::headline($key)));
    }

    public function render(): View
    {
        return view('components.ui.status-badge');
    }
}
