<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Core Administration
            'manage companies',
            'manage branches',
            'manage departments',
            'manage users',
            'manage roles',
            'manage permissions',
            'manage patients',

            // Patient — the plural `patients.*` convention is the one actually enforced by
            // PatientPolicy/modules/Patients routes; a legacy singular `patient.*` set used to
            // coexist unused (dead permissions nothing checked) and has been removed.
            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',
            'patients.merge',
            'patients.export',
            'patients.print',
            'patients.alert.view',
            'patients.alert.manage',
            'patients.documents.view',
            'patients.documents.manage',
            'patients.consents.view',
            'patients.consents.manage',
            'patients.amend.request',
            'patients.amend.approve',
            'patients.portal.manage',

            // Appointment — plural `appointments.*` is the convention actually enforced by
            // AppointmentPolicy/modules/Appointments routes; a legacy singular `appointment.*`
            // set used to coexist unused (dead permissions nothing checked, the same pattern
            // already found and removed for patients) and has been removed.
            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',
            'appointments.confirm',
            'appointments.checkin',
            'appointments.cancel',
            'appointments.reschedule',
            'appointments.no_show',
            'appointments.queue',
            'appointments.token',
            'appointments.override',
            'appointments.export',
            'appointments.print',
            'appointments.manage_schedule',
            'appointments.manage_provider',
            'appointments.manage_holiday',
            'appointments.manage_block',
            'appointments.manage_overbooking',

            // Doctor Schedules
            'schedules.view',
            'schedules.create',
            'schedules.update',
            'schedules.delete',

            // Queue
            'queue.view',
            'queue.manage',

            // OPD
            'opd.view',
            'opd.create',
            'opd.update',
            'opd.consult',

            // Emergency
            'emergency.view',
            'emergency.create',
            'emergency.update',
            'emergency.triage',

            // IPD
            'ipd.view',
            'ipd.create',
            'ipd.update',
            'ipd.admit',
            'ipd.discharge',

            // Bed Management
            'bed.view',
            'bed.allocate',
            'bed.transfer',
            'bed.block',

            // Nursing
            'nursing.view',
            'nursing.create',
            'nursing.update',
            'nursing.administer',

            // Doctor/Physician
            'doctor.view',
            'doctor.create',
            'doctor.update',
            'doctor.consult',

            // EMR
            'emr.view',
            'emr.create',
            'emr.update',
            'emr.amend',
            'emr.approve',

            // Encounter
            'encounters.view',
            'encounters.create',
            'encounters.update',
            'encounters.delete',
            'encounter.start',
            'encounter.complete',
            'encounter.cancel',
            'encounter.amend',
            'encounter.lock',
            'encounter.export',
            'encounter.print',

            // Clinical
            'clinical.note.view',
            'clinical.note.create',
            'clinical.note.update',
            'clinical.vitals.view',
            'clinical.vitals.create',
            'clinical.diagnosis.view',
            'clinical.diagnosis.create',
            'clinical.diagnosis.update',
            'clinical.order.view',
            'clinical.order.create',
            'clinical.order.cancel',
            'clinical.referral.view',
            'clinical.referral.create',
            'clinical.break_glass',

            // Prescription
            'prescription.view',
            'prescription.create',
            'prescription.update',
            'prescription.issue',
            'prescription.cancel',
            'prescription.amend',

            // Pharmacy
            'pharmacy.view',
            'pharmacy.create',
            'pharmacy.update',
            'pharmacy.dispense',
            'pharmacy.adjust',

            // Laboratory
            'laboratory.view',
            'laboratory.create',
            'laboratory.update',
            'laboratory.verify',
            'laboratory.approve',
            'laboratory.release',

            // Radiology
            'radiology.view',
            'radiology.create',
            'radiology.update',
            'radiology.report',

            // OT
            'ot.view',
            'ot.create',
            'ot.update',
            'ot.schedule',

            // ICU
            'icu.view',
            'icu.create',
            'icu.update',

            // Billing
            'billing.view',
            'billing.create',
            'billing.update',
            'billing.discount',
            'billing.refund',
            'billing.payment',

            // Billing — Phase 4
            'billing.dashboard.view',
            'billing.charge.view',
            'billing.charge.create',
            'billing.charge.cancel',
            'billing.invoice.view',
            'billing.invoice.create',
            'billing.invoice.update',
            'billing.invoice.finalize',
            'billing.invoice.cancel',
            'billing.invoice.writeoff',
            'billing.payment.view',
            'billing.payment.create',
            'billing.payment.cancel',
            'billing.receipt.view',
            'billing.receipt.void',
            'billing.refund.request',
            'billing.refund.approve',
            'billing.refund.process',
            'billing.adjustment.request',
            'billing.adjustment.approve',
            'billing.cashier.open',
            'billing.cashier.close',
            'billing.cashier.view',
            'billing.cashier.reconcile',
            'billing.pricing.view',
            'billing.pricing.manage',
            'billing.category.manage',
            'billing.item.manage',
            'billing.corporate.view',
            'billing.corporate.manage',
            'billing.insurance.policy.view',
            'billing.insurance.policy.manage',
            'billing.report.view',
            'billing.settings.manage',

            // Insurance
            'insurance.view',
            'insurance.create',
            'insurance.update',
            'insurance.submit',
            'insurance.approve',

            // Finance
            'finance.view',
            'finance.create',
            'finance.update',
            'finance.post',
            'finance.close',

            // Inventory
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.adjust',
            'inventory.transfer',

            // Procurement
            'procurement.view',
            'procurement.create',
            'procurement.update',
            'procurement.approve',

            // HR
            'hr.view',
            'hr.create',
            'hr.update',
            'hr.attendance',
            'hr.payroll',

            // Reporting
            'reporting.view',
            'reporting.export',
            'reporting.print',

            // Audit
            'audit.view',

            // Activity
            'activity.view',
            'activity.export',

            // Security
            'security.event.view',
            'security.event.resolve',
            'login.history.view',

            // System
            'system.health.view',
            'system.queue.view',
            'system.scheduler.view',

            // Workflow
            'workflow.view',
            'workflow.act',
            'workflow.manage',

            // Settings
            'settings.view',
            'settings.update',

            // Notifications
            'notification.view',
            'notification.manage',

            // Files
            'file.view',
            'file.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create default roles
        $roles = [
            'super_admin' => $permissions,
            'hospital_admin' => [
                'manage companies', 'manage branches', 'manage departments', 'manage users', 'manage patients',
                'patients.view', 'patients.create', 'patients.update', 'patients.delete', 'patients.merge', 'patients.export', 'patients.print',
                'patients.alert.view', 'patients.alert.manage', 'patients.documents.view', 'patients.documents.manage',
                'patients.consents.view', 'patients.consents.manage', 'patients.amend.request', 'patients.amend.approve', 'patients.portal.manage',
                'appointments.view', 'appointments.create', 'appointments.update', 'appointments.delete',
                'appointments.confirm', 'appointments.checkin', 'appointments.cancel', 'appointments.reschedule', 'appointments.no_show',
                'appointments.queue', 'appointments.token', 'appointments.override', 'appointments.export', 'appointments.print',
                'appointments.manage_schedule', 'appointments.manage_provider', 'appointments.manage_holiday',
                'appointments.manage_block', 'appointments.manage_overbooking',
                'schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete',
                'queue.view', 'queue.manage',
                'opd.view', 'opd.create', 'opd.update', 'opd.consult',
                'billing.view', 'billing.create', 'billing.update', 'billing.payment',
                'billing.dashboard.view', 'billing.invoice.view', 'billing.invoice.finalize', 'billing.invoice.cancel', 'billing.invoice.writeoff',
                'billing.refund.approve', 'billing.adjustment.approve',
                'billing.pricing.view', 'billing.pricing.manage', 'billing.category.manage', 'billing.item.manage', 'billing.settings.manage',
                'billing.corporate.view', 'billing.corporate.manage',
                'billing.insurance.policy.view', 'billing.insurance.policy.manage',
                'billing.report.view',
                'reporting.view', 'reporting.export', 'reporting.print',
                'settings.view',
                'workflow.view', 'workflow.act', 'workflow.manage',
            ],
            'doctor' => [
                'patients.view', 'patients.create', 'patients.update',
                'patients.alert.view', 'patients.alert.manage', 'patients.documents.view', 'patients.documents.manage',
                'patients.consents.view', 'patients.amend.request',
                'appointments.view', 'appointments.create', 'appointments.update', 'appointments.cancel',
                'appointments.confirm', 'appointments.checkin', 'appointments.reschedule', 'appointments.no_show', 'appointments.queue',
                'opd.view', 'opd.create', 'opd.update', 'opd.consult',
                'emr.view', 'emr.create', 'emr.update', 'emr.amend', 'emr.approve',
                'prescription.view', 'prescription.create', 'prescription.update', 'prescription.amend',
                'laboratory.view', 'laboratory.create', 'laboratory.update',
                'radiology.view', 'radiology.create', 'radiology.update', 'radiology.report',
                'reporting.view',
            ],
            'nurse' => [
                'patients.view', 'patients.alert.view', 'patients.documents.view',
                'nursing.view', 'nursing.create', 'nursing.update', 'nursing.administer',
                'emr.view', 'emr.create', 'emr.update',
                'reporting.view',
            ],
            'receptionist' => [
                'patients.view', 'patients.create', 'patients.update', 'patients.print',
                'patients.documents.view', 'patients.documents.manage', 'patients.consents.view', 'patients.consents.manage',
                'appointments.view', 'appointments.create', 'appointments.update',
                'appointments.confirm', 'appointments.checkin', 'appointments.cancel', 'appointments.reschedule', 'appointments.no_show',
                'appointments.queue', 'appointments.token', 'appointments.print',
                'schedules.view',
                'queue.view', 'queue.manage',
                'billing.view', 'billing.create', 'billing.update', 'billing.payment',
                'reporting.view',
            ],
            'lab_technician' => [
                'laboratory.view', 'laboratory.create', 'laboratory.update', 'laboratory.verify',
                'reporting.view',
            ],
            'pharmacist' => [
                'pharmacy.view', 'pharmacy.create', 'pharmacy.update', 'pharmacy.dispense', 'pharmacy.adjust',
                'reporting.view',
            ],
            'cashier' => [
                'billing.view', 'billing.create', 'billing.update', 'billing.payment',
                'billing.dashboard.view', 'billing.charge.view', 'billing.invoice.view',
                'billing.payment.view', 'billing.payment.create', 'billing.receipt.view',
                'billing.refund.request',
                'billing.cashier.open', 'billing.cashier.close', 'billing.cashier.view',
                'reporting.view',
            ],
            'accountant' => [
                'finance.view', 'finance.create', 'finance.update', 'finance.post', 'finance.close',
                'billing.view', 'billing.update',
                'billing.dashboard.view', 'billing.invoice.view', 'billing.payment.view', 'billing.receipt.view',
                'billing.refund.approve', 'billing.adjustment.approve',
                'billing.cashier.view', 'billing.cashier.reconcile',
                'billing.corporate.view', 'billing.insurance.policy.view',
                'billing.report.view',
                'reporting.view', 'reporting.export', 'reporting.print',
                'workflow.view', 'workflow.act',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        // Assign super_admin to first user
        $user = User::first();
        if ($user) {
            $user->assignRole('super_admin');
        }
    }
}
