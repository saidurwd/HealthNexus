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
use App\Models\Country;
use App\Models\Currency;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\File;
use App\Models\Icu\IcuAdmissionRequest;
use App\Models\Icu\IcuAssessment;
use App\Models\Icu\IcuCriticalAlert;
use App\Models\Icu\IcuCriticalEvent;
use App\Models\Icu\IcuDischargeSummary;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuProgressNote;
use App\Models\IdentificationType;
use App\Models\InvestigationOrder;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedMovement;
use App\Models\Ipd\IpdDischargeRequest;
use App\Models\Ipd\IpdPatientLeave;
use App\Models\Ipd\IpdWard;
use App\Models\Laboratory\LabCriticalResultAlert;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabQcRun;
use App\Models\Laboratory\LabReport;
use App\Models\Laboratory\LabResult;
use App\Models\Laboratory\LabSpecimen;
use App\Models\Laboratory\LabTest;
use App\Models\Notification;
use App\Models\Nursing\NursingAssessment;
use App\Models\Nursing\NursingAssignment;
use App\Models\Nursing\NursingCarePlan;
use App\Models\Nursing\NursingDischargeChecklist;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingEscalation;
use App\Models\Nursing\NursingHandover;
use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\Nursing\NursingNote;
use App\Models\Organization;
use App\Models\Ot\OtOperativeNote;
use App\Models\Ot\OtPreoperativeAssessment;
use App\Models\Ot\OtRecoveryRecord;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgeryRequest;
use App\Models\Ot\OtSurgicalSafetyChecklist;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyDispensing;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Pharmacy\PharmacySafetyAlert;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\Pharmacy\PharmacyTransfer;
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
use App\Policies\CountryPolicy;
use App\Policies\CurrencyPolicy;
use App\Policies\DiagnosisPolicy;
use App\Policies\EncounterPolicy;
use App\Policies\FilePolicy;
use App\Policies\IcuAdmissionRequestPolicy;
use App\Policies\IcuAlertPolicy;
use App\Policies\IcuAssessmentPolicy;
use App\Policies\IcuCriticalEventPolicy;
use App\Policies\IcuDischargePolicy;
use App\Policies\IcuEpisodePolicy;
use App\Policies\IcuProgressNotePolicy;
use App\Policies\IdentificationTypePolicy;
use App\Policies\InvestigationOrderPolicy;
use App\Policies\IpdAdmissionPolicy;
use App\Policies\IpdBedPolicy;
use App\Policies\IpdDischargePolicy;
use App\Policies\IpdLeavePolicy;
use App\Policies\IpdTransferPolicy;
use App\Policies\IpdWardPolicy;
use App\Policies\LabCriticalResultPolicy;
use App\Policies\LabOrderPolicy;
use App\Policies\LabQcPolicy;
use App\Policies\LabReportPolicy;
use App\Policies\LabResultPolicy;
use App\Policies\LabSpecimenPolicy;
use App\Policies\LabTestPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\NursingAssessmentPolicy;
use App\Policies\NursingAssignmentPolicy;
use App\Policies\NursingCarePlanPolicy;
use App\Policies\NursingDischargeChecklistPolicy;
use App\Policies\NursingEpisodePolicy;
use App\Policies\NursingEscalationPolicy;
use App\Policies\NursingHandoverPolicy;
use App\Policies\NursingMedicationAdministrationPolicy;
use App\Policies\NursingNotePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\OtOperativeNotePolicy;
use App\Policies\OtPreoperativeAssessmentPolicy;
use App\Policies\OtRecoveryPolicy;
use App\Policies\OtSafetyChecklistPolicy;
use App\Policies\OtSurgeryPolicy;
use App\Policies\OtSurgeryRequestPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PharmacyBatchPolicy;
use App\Policies\PharmacyDispensingPolicy;
use App\Policies\PharmacyMedicationPolicy;
use App\Policies\PharmacyOrderPolicy;
use App\Policies\PharmacySafetyAlertPolicy;
use App\Policies\PharmacyStockPolicy;
use App\Policies\PharmacyTransferPolicy;
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
        Organization::class => OrganizationPolicy::class,
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
        PharmacyOrder::class => PharmacyOrderPolicy::class,
        PharmacyDispensing::class => PharmacyDispensingPolicy::class,
        PharmacyStore::class => PharmacyStockPolicy::class,
        PharmacyBatch::class => PharmacyBatchPolicy::class,
        PharmacyTransfer::class => PharmacyTransferPolicy::class,
        PharmacySafetyAlert::class => PharmacySafetyAlertPolicy::class,
        PharmacyMedication::class => PharmacyMedicationPolicy::class,
        IpdAdmission::class => IpdAdmissionPolicy::class,
        IpdBed::class => IpdBedPolicy::class,
        IpdBedMovement::class => IpdTransferPolicy::class,
        IpdDischargeRequest::class => IpdDischargePolicy::class,
        IpdPatientLeave::class => IpdLeavePolicy::class,
        IpdWard::class => IpdWardPolicy::class,
        NursingEpisode::class => NursingEpisodePolicy::class,
        NursingAssignment::class => NursingAssignmentPolicy::class,
        NursingAssessment::class => NursingAssessmentPolicy::class,
        NursingCarePlan::class => NursingCarePlanPolicy::class,
        NursingMedicationAdministration::class => NursingMedicationAdministrationPolicy::class,
        NursingNote::class => NursingNotePolicy::class,
        NursingHandover::class => NursingHandoverPolicy::class,
        NursingEscalation::class => NursingEscalationPolicy::class,
        NursingDischargeChecklist::class => NursingDischargeChecklistPolicy::class,
        OtSurgeryRequest::class => OtSurgeryRequestPolicy::class,
        OtSurgery::class => OtSurgeryPolicy::class,
        OtPreoperativeAssessment::class => OtPreoperativeAssessmentPolicy::class,
        OtSurgicalSafetyChecklist::class => OtSafetyChecklistPolicy::class,
        OtOperativeNote::class => OtOperativeNotePolicy::class,
        OtRecoveryRecord::class => OtRecoveryPolicy::class,
        IcuEpisode::class => IcuEpisodePolicy::class,
        IcuAdmissionRequest::class => IcuAdmissionRequestPolicy::class,
        IcuAssessment::class => IcuAssessmentPolicy::class,
        IcuProgressNote::class => IcuProgressNotePolicy::class,
        IcuCriticalAlert::class => IcuAlertPolicy::class,
        IcuCriticalEvent::class => IcuCriticalEventPolicy::class,
        IcuDischargeSummary::class => IcuDischargePolicy::class,
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
