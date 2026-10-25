<?php

/*
| Module Registry — single source of truth for module keys, prefixes and dependencies
| (guideline ch. 8.2 and 14.1). docs/registry/modules.md is generated from this file by
| `php artisan registry:export`; tests fail when a permission or table prefix is not listed here.
|
| always_on : cannot be switched off.  built : false = specified but not built yet (cannot be enabled).
*/
return [
    'core' => ['name' => 'Core', 'phase' => 0, 'built' => true, 'always_on' => true, 'depends_on' => [], 'permissions' => ['core', 'users', 'roles', 'settings', 'organizations', 'branches', 'departments', 'audit', 'files', 'workflow', 'notifications', 'masterdata', 'system', 'activity', 'login', 'file', 'notification', 'reporting', 'queue', 'security'], 'tables' => ['security', 'user', 'workflow', 'number', 'code', 'report', 'document', 'patient_search'], 'api' => ['auth', 'organizations', 'branches', 'departments', 'users', 'settings', 'notifications', 'tenant']],
    'patient' => ['name' => 'Patient / MPI', 'phase' => 1, 'built' => true, 'always_on' => true, 'depends_on' => ['core'], 'permissions' => ['patient', 'patients'], 'tables' => ['patients', 'patient'], 'api' => ['patients']],
    'appointment' => ['name' => 'Appointment', 'phase' => 2, 'built' => true, 'always_on' => false, 'depends_on' => ['patient'], 'permissions' => ['appointment', 'appointments', 'provider', 'providers', 'schedule', 'doctor', 'schedules'], 'tables' => ['appointment', 'providers', 'provider', 'schedule', 'clinic_sessions', 'doctor_schedules', 'appointments'], 'api' => ['appointments', 'providers']],
    'opd' => ['name' => 'OPD / EMR', 'phase' => 3, 'built' => true, 'always_on' => true, 'depends_on' => ['patient'], 'permissions' => ['clinical', 'encounter', 'encounters', 'prescription', 'emr', 'opd'], 'tables' => ['encounter', 'encounters', 'clinical', 'prescriptions', 'prescription', 'diagnoses', 'investigation_orders', 'vital_signs'], 'api' => ['encounters']],
    'billing' => ['name' => 'Billing', 'phase' => 4, 'built' => true, 'always_on' => true, 'depends_on' => ['patient'], 'permissions' => ['billing'], 'tables' => ['billing', 'charge_event_log'], 'api' => ['billing']],
    'lab' => ['name' => 'Laboratory', 'phase' => 5, 'built' => true, 'always_on' => false, 'depends_on' => ['opd', 'billing'], 'permissions' => ['lab', 'laboratory'], 'tables' => ['lab'], 'api' => ['lab']],
    'radiology' => ['name' => 'Radiology', 'phase' => 6, 'built' => true, 'always_on' => false, 'depends_on' => ['opd', 'billing'], 'permissions' => ['radiology'], 'tables' => ['radiology'], 'api' => ['radiology']],
    'pharmacy' => ['name' => 'Pharmacy', 'phase' => 7, 'built' => true, 'always_on' => false, 'depends_on' => ['opd', 'billing'], 'permissions' => ['pharmacy'], 'tables' => ['pharmacy', 'medications', 'stock_ledger'], 'api' => ['pharmacy']],
    'ipd' => ['name' => 'IPD / Admission / Beds', 'phase' => 8, 'built' => true, 'always_on' => false, 'depends_on' => ['opd', 'billing'], 'permissions' => ['ipd', 'bed'], 'tables' => ['ipd'], 'api' => ['ipd']],
    'nursing' => ['name' => 'Nursing & MAR', 'phase' => 9, 'built' => true, 'always_on' => false, 'depends_on' => ['ipd'], 'permissions' => ['nursing'], 'tables' => ['nursing'], 'api' => ['nursing']],
    'ot' => ['name' => 'OT / Surgery', 'phase' => 10, 'built' => true, 'always_on' => false, 'depends_on' => ['ipd', 'nursing'], 'permissions' => ['ot'], 'tables' => ['ot'], 'api' => ['ot']],
    'icu' => ['name' => 'ICU / Critical Care', 'phase' => 11, 'built' => true, 'always_on' => false, 'depends_on' => ['ipd', 'nursing'], 'permissions' => ['icu'], 'tables' => ['icu'], 'api' => ['icu']],
    'ed' => ['name' => 'Emergency Department', 'phase' => 12, 'built' => false, 'always_on' => false, 'depends_on' => ['opd', 'billing'], 'permissions' => ['ed', 'emergency'], 'tables' => ['ed'], 'api' => ['emergency']],
    'bloodbank' => ['name' => 'Blood Bank', 'phase' => 13, 'built' => false, 'always_on' => false, 'depends_on' => ['patient'], 'permissions' => ['bloodbank'], 'tables' => ['blood'], 'api' => ['blood-bank']],
    'insurance' => ['name' => 'Insurance / Corporate', 'phase' => 14, 'built' => false, 'always_on' => false, 'depends_on' => ['billing'], 'permissions' => ['insurance'], 'tables' => ['insurance', 'corporate'], 'api' => ['insurance']],
    'inventory' => ['name' => 'Inventory / Procurement', 'phase' => 15, 'built' => false, 'always_on' => false, 'depends_on' => ['core'], 'permissions' => ['inventory', 'procurement'], 'tables' => ['inventory', 'procurement'], 'api' => ['inventory', 'procurement']],
    'hr' => ['name' => 'HR / Payroll', 'phase' => 16, 'built' => false, 'always_on' => false, 'depends_on' => ['core'], 'permissions' => ['hr', 'attendance', 'leave', 'payroll', 'compensation'], 'tables' => ['hr'], 'api' => ['hr', 'payroll', 'leave']],
    'portal' => ['name' => 'Patient Portal / CRM', 'phase' => 17, 'built' => false, 'always_on' => false, 'depends_on' => ['patient'], 'permissions' => ['portal', 'crm'], 'tables' => ['portal', 'crm'], 'api' => ['portal', 'mobile']],
    'interop' => ['name' => 'Interoperability', 'phase' => 18, 'built' => false, 'always_on' => false, 'depends_on' => ['core'], 'permissions' => ['interoperability'], 'tables' => ['integration', 'fhir', 'hl7', 'dicom', 'terminology'], 'api' => ['interoperability', 'fhir', 'hl7', 'dicom']],
    'analytics' => ['name' => 'BI / Analytics', 'phase' => 19, 'built' => false, 'always_on' => false, 'depends_on' => ['core'], 'permissions' => ['analytics'], 'tables' => ['fact', 'dim', 'analytics'], 'api' => ['analytics']],
    'ai' => ['name' => 'AI / Clinical Intelligence', 'phase' => 20, 'built' => false, 'always_on' => false, 'depends_on' => ['analytics'], 'permissions' => ['ai'], 'tables' => ['ai'], 'api' => ['ai']],
    'finance' => ['name' => 'Finance & Accounting', 'phase' => 21, 'built' => false, 'always_on' => false, 'depends_on' => ['billing'], 'permissions' => ['finance'], 'tables' => ['fin'], 'api' => ['finance']],
    'facility' => ['name' => 'Facility & Support Services', 'phase' => 22, 'built' => false, 'always_on' => false, 'depends_on' => ['core'], 'permissions' => ['facility'], 'tables' => ['fac'], 'api' => ['facility']],
    'quality' => ['name' => 'Quality, Safety & Compliance', 'phase' => 23, 'built' => false, 'always_on' => false, 'depends_on' => ['core'], 'permissions' => ['quality'], 'tables' => ['qa'], 'api' => ['quality']],
    'specialty' => ['name' => 'Specialty Care & Telemedicine', 'phase' => 24, 'built' => false, 'always_on' => false, 'depends_on' => ['opd'], 'permissions' => ['specialty'], 'tables' => ['mat', 'dial', 'dent', 'physio', 'tele'], 'api' => ['specialty']],
    'platform' => ['name' => 'Platform & SaaS Admin', 'phase' => 25, 'built' => false, 'always_on' => false, 'depends_on' => ['core'], 'permissions' => ['platform'], 'tables' => ['plat'], 'api' => ['platform']],
];
