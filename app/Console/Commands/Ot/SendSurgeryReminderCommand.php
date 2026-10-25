<?php

namespace App\Console\Commands\Ot;

use App\Jobs\Ot\SendSurgeryReminder;
use Illuminate\Console\Command;

class SendSurgeryReminderCommand extends Command
{
    protected $signature = 'ot:send-surgery-reminders';

    protected $description = 'Remind the assigned surgical team of upcoming cases';

    public function handle(): int
    {
        SendSurgeryReminder::dispatchSync();

        $this->info('Remind the assigned surgical team of upcoming cases — done.');

        return self::SUCCESS;
    }
}
