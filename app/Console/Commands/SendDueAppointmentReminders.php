<?php

namespace App\Console\Commands;

use App\Jobs\Appointments\SendAppointmentReminderJob;
use App\Services\Appointments\AppointmentReminderService;
use Illuminate\Console\Command;

class SendDueAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-due-reminders';

    protected $description = 'Dispatch queued jobs for every appointment reminder whose scheduled time has arrived';

    public function handle(AppointmentReminderService $reminders): int
    {
        $due = $reminders->dueReminders();

        foreach ($due as $reminder) {
            SendAppointmentReminderJob::dispatch($reminder);
        }

        $this->info("Dispatched {$due->count()} due reminder(s).");

        return self::SUCCESS;
    }
}
