<?php

namespace Tests\Feature\Phase0;

use App\Support\RegistryInspector;
use Tests\TestCase;

/** CI gate (guideline H-01): permissions and tables must be registered in config/modules.php. */
class RegistryConformanceTest extends TestCase
{
    public function test_every_permission_prefix_and_table_belongs_to_a_registered_module(): void
    {
        $this->assertSame([], app(RegistryInspector::class)->problems());
    }

    public function test_module_dependencies_point_at_registered_modules(): void
    {
        foreach (config('modules') as $key => $module) {
            foreach ($module['depends_on'] as $dependency) {
                $this->assertArrayHasKey($dependency, config('modules'), "$key depends on unknown $dependency");
            }
        }
    }

    public function test_every_listed_event_has_a_registry_row(): void
    {
        $markdown = app(RegistryInspector::class)->eventsMarkdown();

        foreach (glob(app_path('Events/*/*.php')) as $file) {
            $this->assertStringContainsString('`'.basename($file, '.php').'`', $markdown);
        }
    }
}
