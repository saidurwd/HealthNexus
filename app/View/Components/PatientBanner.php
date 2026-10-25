<?php

namespace App\View\Components;

use App\Models\Patient;
use App\Services\Ux\PatientBannerData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PatientBanner extends Component
{
    public array $banner;

    public function __construct(public Patient $patient)
    {
        $this->banner = app(PatientBannerData::class)->for($patient);
    }

    public function render(): View
    {
        return view('components.patient-banner');
    }
}
