<?php

namespace App\Console\Commands\Nursing;

use App\Jobs\Nursing\DetectOverdueNursingTasks;
use Illuminate\Console\Command;

class DetectOverdueNursingTasksCommand extends Command
{
    protected $signature = 'nursing:detect-overdue-tasks';

    protected $description = 'Flag overdue nursing tasks and notify';

    public function handle(): int
    {
        DetectOverdueNursingTasks::dispatchSync();

        $this->info('Flag overdue nursing tasks and notify — done.');

        return self::SUCCESS;
    }
}
