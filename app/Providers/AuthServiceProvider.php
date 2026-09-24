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
use App\Models\Laboratory\LabCriticalResultAlert;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabQcRun;
use App\Models\Laboratory\LabReport;
use App\Models\Laboratory\LabResult;
use App\Models\Laboratory\LabSpecimen;
use App\Models\Laboratory\LabTest;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Radiology\RadiologyCriticalFinding;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyPacsServer;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\Radiology\RadiologyReport;
use App\Models\Radiology\RadiologyStudy;
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
use App\Policies\LabCriticalResultPolicy;
use App\Policies\LabOrderPolicy;
use App\Policies\LabQcPolicy;
use App\Policies\LabReportPolicy;
use App\Policies\LabResultPolicy;
use App\Policies\LabSpecimenPolicy;
use App\Policies\LabTestPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\RadiologyCriticalFindingPolicy;
use App\Policies\RadiologyExaminationPolicy;
use App\Policies\RadiologyOrderPolicy;
use App\Policies\RadiologyPacsPolicy;
use App\Policies\RadiologyProcedurePolicy;
use App\Policies\RadiologyReportPolicy;
use App\Policies\RadiologyStudyPolicy;
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
        LabOrder::class => LabOrderPolicy::class,
        LabSpecimen::class => LabSpecimenPolicy::class,
        LabResult::class => LabResultPolicy::class,
        LabReport::class => LabReportPolicy::class,
        LabCriticalResultAlert::class => LabCriticalResultPolicy::class,
        LabTest::class => LabTestPolicy::class,
        LabQcRun::class => LabQcPolicy::class,
        RadiologyOrder::class => RadiologyOrderPolicy::class,
        RadiologyExamination::class => RadiologyExaminationPolicy::class,
        RadiologyStudy::class => RadiologyStudyPolicy::class,
        RadiologyReport::class => RadiologyReportPolicy::class,
        RadiologyCriticalFinding::class => RadiologyCriticalFindingPolicy::class,
        RadiologyProcedure::class => RadiologyProcedurePolicy::class,
        RadiologyPacsServer::class => RadiologyPacsPolicy::class,
        \App\Models\Pharmacy\PharmacyOrder::class => \App\Policies\PharmacyOrderPolicy::class,
        \App\Models\Pharmacy\PharmacyDispensing::class => \App\Policies\PharmacyDispensingPolicy::class,
        \App\Models\Pharmacy\PharmacyStore::class => \App\Policies\PharmacyStockPolicy::class,
        \App\Models\Pharmacy\PharmacyBatch::class => \App\Policies\PharmacyBatchPolicy::class,
        \App\Models\Pharmacy\PharmacyTransfer::class => \App\Policies\PharmacyTransferPolicy::class,
        \App\Models\Pharmacy\PharmacySafetyAlert::class => \App\Policies\PharmacySafetyAlertPolicy::class,
        \App\Models\Pharmacy\PharmacyMedication::class => \App\Policies\PharmacyMedicationPolicy::class,
        \App\Models\Ipd\IpdAdmission::class => \App\Policies\IpdAdmissionPolicy::class,
        \App\Models\Ipd\IpdBed::class => \App\Policies\IpdBedPolicy::class,
        \App\Models\Ipd\IpdBedMovement::class => \App\Policies\IpdTransferPolicy::class,
        \App\Models\Ipd\IpdDischargeRequest::class => \App\Policies\IpdDischargePolicy::class,
        \App\Models\Ipd\IpdPatientLeave::class => \App\Policies\IpdLeavePolicy::class,
        \App\Models\Ipd\IpdWard::class => \App\Policies\IpdWardPolicy::class,
        \App\Models\Nursing\NursingEpisode::class => \App\Policies\NursingEpisodePolicy::class,
        \App\Models\Nursing\NursingAssignment::class => \App\Policies\NursingAssignmentPolicy::class,
        \App\Models\Nursing\NursingAssessment::class => \App\Policies\NursingAssessmentPolicy::class,
        \App\Models\Nursing\NursingCarePlan::class => \App\Policies\NursingCarePlanPolicy::class,
        \App\Models\Nursing\NursingMedicationAdministration::class => \App\Policies\NursingMedicationAdministrationPolicy::class,
        \App\Models\Nursing\NursingNote::class => \App\Policies\NursingNotePolicy::class,
        \App\Models\Nursing\NursingHandover::class => \App\Policies\NursingHandoverPolicy::class,
        \App\Models\Nursing\NursingEscalation::class => \App\Policies\NursingEscalationPolicy::class,
        \App\Models\Nursing\NursingDischargeChecklist::class => \App\Policies\NursingDischargeChecklistPolicy::class,
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
