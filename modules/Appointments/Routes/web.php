<?php

use Illuminate\Support\Facades\Route;
use Modules\Appointments\Http\Controllers\AppointmentController;
use Modules\Appointments\Http\Controllers\AppointmentRoomController;
use Modules\Appointments\Http\Controllers\AppointmentTypeController;
use Modules\Appointments\Http\Controllers\CalendarController;
use Modules\Appointments\Http\Controllers\DoctorScheduleController;
use Modules\Appointments\Http\Controllers\HospitalHolidayController;
use Modules\Appointments\Http\Controllers\OpdConsultationController;
use Modules\Appointments\Http\Controllers\ProviderController;
use Modules\Appointments\Http\Controllers\ProviderUnavailabilityController;
use Modules\Appointments\Http\Controllers\QueueController;

// Route-level gates mirror the plural appointments.* permission convention enforced by
// AppointmentPolicy. Previously this whole module (appointments, schedules, queue, OPD) sat
// behind a single blanket 'can:manage companies' gate, which silently locked doctor/nurse/
// receptionist out of every appointment route despite them holding the documented
// appointments.* grants in PermissionSeeder — the same lockout bug already found and fixed for
// the Patients module in Phase 1.
Route::middleware('can:appointments.view')->group(function () {
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
});

Route::middleware('can:appointments.create')->group(function () {
    Route::get('appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
});

Route::middleware('can:appointments.view')->group(function () {
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::get('appointments/{appointment}/history', [AppointmentController::class, 'history'])->name('appointments.history');
});

Route::middleware('can:appointments.create')->group(function () {
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::post('appointments/{appointment}/follow-up', [AppointmentController::class, 'createFollowUp'])->name('appointments.follow-up');
});

Route::middleware('can:appointments.update')->group(function () {
    Route::get('appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('appointments/{appointment}/notes', [AppointmentController::class, 'addNote'])->name('appointments.notes.add');
    Route::post('appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('appointments/{appointment}/documents', [AppointmentController::class, 'uploadDocument'])->name('appointments.documents.upload');
    Route::delete('appointments/{appointment}/documents/{document}', [AppointmentController::class, 'deleteDocument'])->name('appointments.documents.delete');
});

Route::middleware('can:appointments.view')->group(function () {
    Route::get('appointments/{appointment}/documents/{document}/download', [AppointmentController::class, 'downloadDocument'])->name('appointments.documents.download');
});

Route::middleware('can:appointments.delete')->group(function () {
    Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
});

Route::middleware('can:appointments.confirm')->group(function () {
    Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
});

Route::middleware('can:appointments.checkin')->group(function () {
    Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in');
    Route::post('appointments/{appointment}/start', [AppointmentController::class, 'start'])->name('appointments.start');
});

Route::middleware('can:appointments.cancel')->group(function () {
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
});

Route::middleware('can:appointments.reschedule')->group(function () {
    Route::post('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
});

Route::middleware('can:appointments.no_show')->group(function () {
    Route::post('appointments/{appointment}/no-show', [AppointmentController::class, 'noShow'])->name('appointments.no-show');
});

Route::middleware('can:appointments.print')->group(function () {
    Route::get('appointments/{appointment}/print', [AppointmentController::class, 'print'])->name('appointments.print');
});

// --- Provider/schedule/holiday/unavailability/room administration ---

Route::middleware('can:appointments.manage_schedule')->group(function () {
    Route::get('schedules/create', [DoctorScheduleController::class, 'create'])->name('schedules.create');
    Route::get('schedules', [DoctorScheduleController::class, 'index'])->name('schedules.index');
    Route::post('schedules', [DoctorScheduleController::class, 'store'])->name('schedules.store');
    Route::get('schedules/{schedule}', [DoctorScheduleController::class, 'show'])->name('schedules.show');
    Route::get('schedules/{schedule}/edit', [DoctorScheduleController::class, 'edit'])->name('schedules.edit');
    Route::put('schedules/{schedule}', [DoctorScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('schedules/{schedule}', [DoctorScheduleController::class, 'destroy'])->name('schedules.destroy');
    Route::post('schedules/{schedule}/generate-slots', [DoctorScheduleController::class, 'generateSlots'])->name('schedules.generate-slots');
});

Route::middleware('can:appointments.manage_provider')->group(function () {
    Route::resource('providers', ProviderController::class)->except(['show']);
    Route::get('providers/{provider}', [ProviderController::class, 'show'])->name('providers.show');
});

Route::middleware('can:appointments.manage_holiday')->group(function () {
    Route::resource('holidays', HospitalHolidayController::class)->only(['index', 'create', 'store', 'destroy']);
});

Route::middleware('can:appointments.manage_block')->group(function () {
    Route::get('provider-unavailability', [ProviderUnavailabilityController::class, 'index'])->name('provider-unavailability.index');
    Route::post('provider-unavailability', [ProviderUnavailabilityController::class, 'store'])->name('provider-unavailability.store');
    Route::delete('provider-unavailability/{unavailability}', [ProviderUnavailabilityController::class, 'destroy'])->name('provider-unavailability.destroy');
});

Route::middleware('can:appointments.manage_schedule')->group(function () {
    Route::resource('appointment-rooms', AppointmentRoomController::class)->except(['show']);
    Route::resource('appointment-types', AppointmentTypeController::class)->except(['show']);
});

// --- Queue management ---

Route::middleware('can:appointments.queue')->group(function () {
    Route::get('queue', [QueueController::class, 'index'])->name('queue.index');
    Route::post('queue/call-next', [QueueController::class, 'callNext'])->name('queue.call-next');
    Route::post('queue/{token}/recall', [QueueController::class, 'recall'])->name('queue.recall');
    Route::post('queue/{token}/skip', [QueueController::class, 'skip'])->name('queue.skip');
    Route::post('queue/{token}/transfer', [QueueController::class, 'transfer'])->name('queue.transfer');
    Route::post('queue/{token}/cancel', [QueueController::class, 'cancel'])->name('queue.cancel');
    Route::post('queue/{token}/start', [QueueController::class, 'start'])->name('queue.start');
    Route::post('queue/{token}/complete', [QueueController::class, 'complete'])->name('queue.complete');
    Route::get('queue/slots', [QueueController::class, 'searchSlots'])->name('queue.slots');
});

// --- Calendar & dashboard ---

Route::middleware('can:appointments.view')->group(function () {
    Route::get('appointments-calendar', [CalendarController::class, 'day'])->name('appointments.calendar.day');
    Route::get('appointments-calendar/provider', [CalendarController::class, 'provider'])->name('appointments.calendar.provider');
});

// --- OPD consultation (Phase 3 boundary — see OpdConsultationController) ---

Route::middleware('can:appointments.checkin')->group(function () {
    Route::post('appointments/{appointment}/opd-check-in', [OpdConsultationController::class, 'checkIn'])->name('opd.check-in');
});

Route::middleware('can:appointments.view')->group(function () {
    Route::get('appointments/{appointment}/consultation', [OpdConsultationController::class, 'show'])->name('opd.consultation');
});

Route::middleware('can:appointments.update')->group(function () {
    Route::post('opd/vital-signs', [OpdConsultationController::class, 'storeVitalSigns'])->name('opd.vital-signs');
    Route::post('opd/diagnoses', [OpdConsultationController::class, 'storeDiagnosis'])->name('opd.diagnoses');
    Route::post('opd/investigation-orders', [OpdConsultationController::class, 'storeInvestigationOrder'])->name('opd.investigation-orders');
    Route::post('opd/prescriptions', [OpdConsultationController::class, 'storePrescription'])->name('opd.prescriptions');
    Route::post('appointments/{appointment}/status/in-progress', [OpdConsultationController::class, 'markInProgress'])->name('opd.status.in-progress');
    Route::post('appointments/{appointment}/status/completed', [OpdConsultationController::class, 'markCompleted'])->name('opd.status.completed');
});
