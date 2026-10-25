<?php

namespace App\Console\Commands;

use App\Support\RegistryInspector;
use Illuminate\Console\Command;

class ExportRegistry extends Command
{
    protected $signature = 'registry:export {--check : Fail instead of writing when something is not registered}';

    protected $description = 'Generate docs/registry/* from config/modules.php, the permission seeder, the schema and the event bus';

    public function handle(RegistryInspector $inspector): int
    {
        $problems = $inspector->problems();

        if ($this->option('check')) {
            foreach ($problems as $problem) {
                $this->error($problem);
            }

            return $problems === [] ? self::SUCCESS : self::FAILURE;
        }

        $dir = base_path('docs/registry');
        is_dir($dir) || mkdir($dir, 0755, true);

        file_put_contents("$dir/modules.md", $inspector->modulesMarkdown());
        file_put_contents("$dir/permissions.md", $inspector->permissionsMarkdown());
        file_put_contents("$dir/tables.md", $inspector->tablesMarkdown());
        file_put_contents(base_path('docs/events.md'), $inspector->eventsMarkdown());

        $this->info('Registry written to docs/registry and docs/events.md');

        foreach ($problems as $problem) {
            $this->warn($problem);
        }

        return self::SUCCESS;
    }
}
