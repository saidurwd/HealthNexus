<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Encounter;
use App\Models\IdentificationType;
use App\Models\Patient;
use App\Models\State;
use App\Policies\BranchPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CountryPolicy;
use App\Policies\CurrencyPolicy;
use App\Policies\EncounterPolicy;
use App\Policies\IdentificationTypePolicy;
use App\Policies\PatientPolicy;
use App\Policies\StatePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Company::class => CompanyPolicy::class,
        Branch::class => BranchPolicy::class,
        Patient::class => PatientPolicy::class,
        Encounter::class => EncounterPolicy::class,
        Country::class => CountryPolicy::class,
        State::class => StatePolicy::class,
        Currency::class => CurrencyPolicy::class,
        IdentificationType::class => IdentificationTypePolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
