<?php

use Illuminate\Support\Facades\Route;
use Modules\Appointments\Http\Controllers\AppointmentController;
use Modules\Appointments\Http\Controllers\DoctorScheduleController;
use Modules\Appointments\Http\Controllers\OpdConsultationController;
use Modules\Appointments\Http\Controllers\QueueController;

Route::middleware('can:manage companies')->group(function () {
    // Phase 2: Appointment + OPD
    Route::resource('appointments', AppointmentController::class);
    Route::resource('schedules', DoctorScheduleController::class)->except(['show']);
    Route::get('schedules/{schedule}', [DoctorScheduleController::class, 'show'])->name('schedules.show');

    // Queue management
    Route::get('queue', [QueueController::class, 'index'])->name('queue.index');
    Route::post('queue/call-next', [QueueController::class, 'callNext'])->name('queue.call-next');
    Route::post('queue/{token}/start', [QueueController::class, 'start'])->name('queue.start');
    Route::post('queue/{token}/complete', [QueueController::class, 'complete'])->name('queue.complete');
    Route::get('queue/slots', [QueueController::class, 'searchSlots'])->name('queue.slots');

    Route::post('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('appointments/{appointment}/no-show', [AppointmentController::class, 'noShow'])->name('appointments.no-show');
    Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in');
    Route::post('appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('appointments/{appointment}/follow-up', [OpdConsultationController::class, 'createFollowUp'])->name('appointments.follow-up');
    Route::post('appointments/{appointment}/notes', [AppointmentController::class, 'addNote'])->name('appointments.notes.add');
    Route::get('appointments/{appointment}/history', [AppointmentController::class, 'history'])->name('appointments.history');

    // OPD consultation
    Route::get('appointments/{appointment}/consultation', [OpdConsultationController::class, 'show'])->name('opd.consultation');
    Route::post('opd/vital-signs', [OpdConsultationController::class, 'storeVitalSigns'])->name('opd.vital-signs');
    Route::post('opd/diagnoses', [OpdConsultationController::class, 'storeDiagnosis'])->name('opd.diagnoses');
    Route::post('opd/investigation-orders', [OpdConsultationController::class, 'storeInvestigationOrder'])->name('opd.investigation-orders');
    Route::post('opd/prescriptions', [OpdConsultationController::class, 'storePrescription'])->name('opd.prescriptions');
    Route::post('appointments/{appointment}/status/in-progress', [OpdConsultationController::class, 'markInProgress'])->name('opd.status.in-progress');
    Route::post('appointments/{appointment}/status/completed', [OpdConsultationController::class, 'markCompleted'])->name('opd.status.completed');
});
