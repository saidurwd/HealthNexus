<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingCashierSession;
use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingPriceList;
use App\Models\Billing\BillingReceipt;
use App\Models\Billing\BillingRefund;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\File;
use App\Models\IdentificationType;
use App\Models\InvestigationOrder;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\State;
use App\Policies\AppointmentPolicy;
use App\Policies\BillingAdjustmentPolicy;
use App\Policies\BillingCashierSessionPolicy;
use App\Policies\BillingCategoryPolicy;
use App\Policies\BillingChargePolicy;
use App\Policies\BillingCorporatePolicy;
use App\Policies\BillingInsurancePolicyPolicy;
use App\Policies\BillingInvoicePolicy;
use App\Policies\BillingItemPolicy;
use App\Policies\BillingPaymentPolicy;
use App\Policies\BillingPriceListPolicy;
use App\Policies\BillingReceiptPolicy;
use App\Policies\BillingRefundPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CountryPolicy;
use App\Policies\CurrencyPolicy;
use App\Policies\DiagnosisPolicy;
use App\Policies\EncounterPolicy;
use App\Policies\FilePolicy;
use App\Policies\IdentificationTypePolicy;
use App\Policies\InvestigationOrderPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\StatePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Company::class => CompanyPolicy::class,
        Branch::class => BranchPolicy::class,
        Patient::class => PatientPolicy::class,
        Appointment::class => AppointmentPolicy::class,
        Diagnosis::class => DiagnosisPolicy::class,
        Prescription::class => PrescriptionPolicy::class,
        InvestigationOrder::class => InvestigationOrderPolicy::class,
        Encounter::class => EncounterPolicy::class,
        Country::class => CountryPolicy::class,
        State::class => StatePolicy::class,
        Currency::class => CurrencyPolicy::class,
        IdentificationType::class => IdentificationTypePolicy::class,
        Notification::class => NotificationPolicy::class,
        File::class => FilePolicy::class,
        BillingInvoice::class => BillingInvoicePolicy::class,
        BillingPayment::class => BillingPaymentPolicy::class,
        BillingReceipt::class => BillingReceiptPolicy::class,
        BillingCharge::class => BillingChargePolicy::class,
        BillingRefund::class => BillingRefundPolicy::class,
        BillingAdjustment::class => BillingAdjustmentPolicy::class,
        BillingCashierSession::class => BillingCashierSessionPolicy::class,
        BillingItem::class => BillingItemPolicy::class,
        BillingCategory::class => BillingCategoryPolicy::class,
        BillingPriceList::class => BillingPriceListPolicy::class,
        BillingCorporate::class => BillingCorporatePolicy::class,
        BillingInsurancePolicy::class => BillingInsurancePolicyPolicy::class,
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
