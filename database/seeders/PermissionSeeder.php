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

            // Patient
            'patient.view',
            'patient.create',
            'patient.update',
            'patient.delete',
            'patient.merge',
            'patient.export',
            'patient.alert.view',
            'patient.alert.manage',
            'patient.amend',

            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',
            'patients.merge',
            'patients.export',

            // Appointment
            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',
            'appointment.view',
            'appointment.create',
            'appointment.update',
            'appointment.cancel',

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
                'patient.view', 'patient.create', 'patient.update', 'patient.export',
                'patients.view', 'patients.create', 'patients.update', 'patients.delete', 'patients.merge', 'patients.export',
                'appointments.view', 'appointments.create', 'appointments.update', 'appointments.delete',
                'schedules.view',
                'queue.view', 'queue.manage',
                'opd.view', 'opd.create', 'opd.update', 'opd.consult',
                'billing.view', 'billing.create', 'billing.update', 'billing.payment',
                'reporting.view', 'reporting.export', 'reporting.print',
                'settings.view',
            ],
            'doctor' => [
                'patient.view', 'patient.create', 'patient.update',
                'patients.view', 'patients.create', 'patients.update',
                'appointment.view', 'appointment.create', 'appointment.update', 'appointment.cancel',
                'opd.view', 'opd.create', 'opd.update', 'opd.consult',
                'emr.view', 'emr.create', 'emr.update', 'emr.amend', 'emr.approve',
                'prescription.view', 'prescription.create', 'prescription.update', 'prescription.amend',
                'laboratory.view', 'laboratory.create', 'laboratory.update',
                'radiology.view', 'radiology.create', 'radiology.update', 'radiology.report',
                'reporting.view',
            ],
            'nurse' => [
                'patient.view',
                'nursing.view', 'nursing.create', 'nursing.update', 'nursing.administer',
                'emr.view', 'emr.create', 'emr.update',
                'reporting.view',
            ],
            'receptionist' => [
                'patient.view', 'patient.create', 'patient.update',
                'patients.view', 'patients.create', 'patients.update',
                'appointments.view', 'appointments.create', 'appointments.update',
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
                'reporting.view',
            ],
            'accountant' => [
                'finance.view', 'finance.create', 'finance.update', 'finance.post', 'finance.close',
                'billing.view', 'billing.update',
                'reporting.view', 'reporting.export', 'reporting.print',
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
