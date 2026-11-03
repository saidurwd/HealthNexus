<?php

namespace Tests\Feature\Demo;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\Demo\DemoAccess;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    use RefreshDatabase;

    private function build(): array
    {
        $this->seed(PermissionSeeder::class);
        $organization = Organization::factory()->create(['code' => 'DEMO1']);
        $branch = Branch::factory()->create(['organization_id' => $organization->id]);

        return [app(DemoAccess::class)->build($organization, $branch, 'secret-demo-pass'), $organization];
    }

    public function test_a_super_user_viewers_managers_job_roles_and_a_no_access_member_are_made(): void
    {
        [$rows] = $this->build();
        $emails = array_column($rows, 'email');

        $this->assertContains('superuser@demo1.example.invalid', $emails);
        $this->assertContains('noaccess@demo1.example.invalid', $emails);
        $this->assertContains('ed.manager@demo1.example.invalid', $emails);
        $this->assertContains('role.doctor@demo1.example.invalid', $emails);
        $this->assertTrue(User::where('email', 'superuser@demo1.example.invalid')->first()->hasRole('super_admin'));
        $this->assertTrue(User::where('email', 'noaccess@demo1.example.invalid')->first()->getAllPermissions()->isEmpty());
    }

    public function test_a_module_viewer_can_read_but_not_change_and_a_manager_can_do_both(): void
    {
        $this->build();
        $viewer = User::where('email', 'ed.viewer@demo1.example.invalid')->firstOrFail();
        $manager = User::where('email', 'ed.manager@demo1.example.invalid')->firstOrFail();

        $this->assertTrue($viewer->can('ed.board.view'));
        $this->assertFalse($viewer->can('ed.visit.create'));
        $this->assertFalse($viewer->can('ed.disposition.decide'));
        $this->assertTrue($manager->can('ed.disposition.decide'));
        $this->assertFalse($manager->can('lab.order.create') || $manager->can('pharmacy.dispense.create'), 'a module manager gets only that module');
    }

    public function test_every_built_module_has_a_viewer_that_can_see_something(): void
    {
        $this->build();
        $missing = [];
        foreach (config('modules') as $key => $module) {
            if (! $module['built'] || in_array($key, ['core'], true)) {
                continue;
            }
            $viewer = User::where('email', "$key.viewer@demo1.example.invalid")->first();
            if (! $viewer || $viewer->getAllPermissions()->isEmpty()) {
                $missing[] = $key;
            }
        }
        $this->assertSame([], $missing);
    }

    public function test_running_it_twice_does_not_duplicate_users(): void
    {
        [$first, $organization] = $this->build();
        $branch = Branch::where('organization_id', $organization->id)->first();
        $second = app(DemoAccess::class)->build($organization, $branch, 'secret-demo-pass');

        $this->assertSame(count($first), count($second));
        $this->assertSame(count($first), User::count());
    }
}
