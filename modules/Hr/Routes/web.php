<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\Web\HrDeskController;
use Modules\Hr\Http\Controllers\Web\HrEmployeeController;
use Modules\Hr\Http\Controllers\Web\HrLeaveController;
use Modules\Hr\Http\Controllers\Web\HrPayrollController;
use Modules\Hr\Http\Controllers\Web\HrSelfController;
use Modules\Hr\Http\Controllers\Web\HrTimeController;

Route::prefix('hr')->name('hr.')->group(function () {
    Route::get('/', [HrDeskController::class, 'dashboard'])->name('dashboard');

    // me and my team
    Route::get('me', [HrSelfController::class, 'home'])->name('me');
    Route::post('me/leave', [HrSelfController::class, 'requestLeave'])->name('me.leave');
    Route::post('me/leave/{leave}/cancel', [HrSelfController::class, 'cancelLeave'])->name('me.leave.cancel');
    Route::post('me/corrections', [HrSelfController::class, 'requestCorrection'])->name('me.corrections');
    Route::get('me/payslips/{line}', [HrSelfController::class, 'payslip'])->name('me.payslip');
    Route::post('me/appraisals/{appraisal}/acknowledge', [HrSelfController::class, 'acknowledge'])->name('me.appraisals.acknowledge');
    Route::get('team', [HrSelfController::class, 'team'])->name('team');

    // people
    Route::get('employees', [HrEmployeeController::class, 'index'])->name('employees');
    Route::post('employees', [HrEmployeeController::class, 'store'])->name('employees.store');
    Route::get('employees/{employee}', [HrEmployeeController::class, 'show'])->name('employees.show');
    Route::post('employees/{employee}', [HrEmployeeController::class, 'update'])->name('employees.update');
    Route::post('employees/{employee}/status', [HrEmployeeController::class, 'status'])->name('employees.status');
    Route::post('employees/{employee}/user', [HrEmployeeController::class, 'linkUser'])->name('employees.user');
    Route::post('employees/{employee}/revoke-access', [HrEmployeeController::class, 'revokeAccess'])->name('employees.revoke');
    Route::post('employees/{employee}/contracts', [HrEmployeeController::class, 'addContract'])->name('employees.contracts');
    Route::post('employees/{employee}/credentials', [HrEmployeeController::class, 'addCredential'])->name('employees.credentials');
    Route::post('credentials/{credential}/verify', [HrEmployeeController::class, 'verifyCredential'])->name('credentials.verify');
    Route::post('employees/{employee}/documents', [HrEmployeeController::class, 'addDocument'])->name('employees.documents');
    Route::post('employees/{employee}/checklists', [HrEmployeeController::class, 'openChecklist'])->name('employees.checklists');
    Route::post('checklist-items/{item}/tick', [HrEmployeeController::class, 'tick'])->name('checklist.tick');
    Route::post('employees/{employee}/structures', [HrEmployeeController::class, 'newStructure'])->name('employees.structures');
    Route::post('employees/{employee}/dependents', [HrEmployeeController::class, 'addDependent'])->name('employees.dependents');
    Route::get('jobs', [HrDeskController::class, 'jobs'])->name('jobs');
    Route::post('jobs', [HrDeskController::class, 'storeJob'])->name('jobs.store');
    Route::post('positions', [HrDeskController::class, 'storePosition'])->name('positions.store');
    Route::get('provider-links', [HrDeskController::class, 'providerLinks'])->name('provider-links');
    Route::post('provider-links/propose', [HrDeskController::class, 'proposeLinks'])->name('provider-links.propose');
    Route::post('provider-links/{link}', [HrDeskController::class, 'decideLink'])->name('provider-links.decide');
    Route::post('backfill', [HrDeskController::class, 'backfill'])->name('backfill');
    Route::get('vacancies', [HrDeskController::class, 'vacancies'])->name('vacancies');
    Route::post('vacancies', [HrDeskController::class, 'storeVacancy'])->name('vacancies.store');
    Route::post('vacancies/{vacancy}/candidates', [HrDeskController::class, 'storeCandidate'])->name('candidates.store');
    Route::post('candidates/{candidate}', [HrDeskController::class, 'moveCandidate'])->name('candidates.move');
    Route::get('training', [HrDeskController::class, 'training'])->name('training');
    Route::post('training', [HrDeskController::class, 'storeTraining'])->name('training.store');
    Route::post('training/{training}/records', [HrDeskController::class, 'recordTraining'])->name('training.record');
    Route::post('appraisal-cycles', [HrDeskController::class, 'openCycle'])->name('cycles.store');
    Route::post('appraisals/{appraisal}', [HrDeskController::class, 'submitAppraisal'])->name('appraisals.submit');
    Route::get('reports', [HrDeskController::class, 'reports'])->name('reports');

    // time
    Route::get('shifts', [HrTimeController::class, 'shifts'])->name('shifts');
    Route::post('shifts', [HrTimeController::class, 'storeShift'])->name('shifts.store');
    Route::post('staffing', [HrTimeController::class, 'storeRequirement'])->name('staffing.store');
    Route::get('roster', [HrTimeController::class, 'roster'])->name('roster');
    Route::post('roster', [HrTimeController::class, 'assign'])->name('roster.assign');
    Route::post('roster/publish', [HrTimeController::class, 'publish'])->name('roster.publish');
    Route::post('roster/{entry}/remove', [HrTimeController::class, 'unassign'])->name('roster.remove');
    Route::get('attendance', [HrTimeController::class, 'attendance'])->name('attendance');
    Route::post('attendance/process', [HrTimeController::class, 'process'])->name('attendance.process');
    Route::post('attendance/punches', [HrTimeController::class, 'importPunches'])->name('attendance.punches');
    Route::post('attendance/map-code', [HrTimeController::class, 'mapCode'])->name('attendance.map');
    Route::post('devices', [HrTimeController::class, 'storeDevice'])->name('devices.store');
    Route::get('corrections', [HrTimeController::class, 'corrections'])->name('corrections');
    Route::post('corrections/{regularization}', [HrTimeController::class, 'decideCorrection'])->name('corrections.decide');
    Route::get('overtime', [HrTimeController::class, 'overtime'])->name('overtime');
    Route::post('overtime/sync', [HrTimeController::class, 'syncOvertime'])->name('overtime.sync');
    Route::post('overtime/{entry}', [HrTimeController::class, 'decideOvertime'])->name('overtime.decide');

    // leave
    Route::get('leave', [HrLeaveController::class, 'index'])->name('leave');
    Route::post('leave/{leave}', [HrLeaveController::class, 'decide'])->name('leave.decide');
    Route::get('leave-balances', [HrLeaveController::class, 'balances'])->name('leave.balances');
    Route::post('leave-balances/adjust', [HrLeaveController::class, 'adjust'])->name('leave.adjust');
    Route::post('leave-balances/accrue', [HrLeaveController::class, 'accrue'])->name('leave.accrue');
    Route::get('leave-types', [HrLeaveController::class, 'types'])->name('leave.types');
    Route::post('leave-types', [HrLeaveController::class, 'storeType'])->name('leave.types.store');

    // pay
    Route::get('payroll', [HrPayrollController::class, 'runs'])->name('payroll');
    Route::post('payroll', [HrPayrollController::class, 'createRun'])->name('payroll.store');
    Route::get('payroll/{run}', [HrPayrollController::class, 'show'])->name('payroll.show');
    Route::post('payroll/{run}/move', [HrPayrollController::class, 'move'])->name('payroll.move');
    Route::post('payroll/{run}/files', [HrPayrollController::class, 'prepareFile'])->name('payroll.files');
    Route::post('payroll/{run}/adjustments', [HrPayrollController::class, 'addAdjustment'])->name('payroll.adjustments');
    Route::get('payment-files/{batch}', [HrPayrollController::class, 'downloadFile'])->name('payment-files.download');
    Route::get('payslip-lines/{line}', [HrPayrollController::class, 'payslip'])->name('payslips.show');
    Route::get('compensation', [HrPayrollController::class, 'components'])->name('compensation');
    Route::post('compensation/components', [HrPayrollController::class, 'storeComponent'])->name('compensation.components');
    Route::post('compensation/slabs', [HrPayrollController::class, 'storeSlabs'])->name('compensation.slabs');
    Route::post('loans', [HrPayrollController::class, 'requestLoan'])->name('loans.store');
    Route::post('loans/{loan}/approve', [HrPayrollController::class, 'approveLoan'])->name('loans.approve');
});
