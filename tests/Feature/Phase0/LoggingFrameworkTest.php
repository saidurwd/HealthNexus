<?php

namespace Tests\Feature\Phase0;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SecurityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LoggingFrameworkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['activity.view', 'activity.export', 'security.event.view', 'security.event.resolve'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create(['password' => 'password']);
        $this->admin->givePermissionTo(['activity.view', 'security.event.view', 'security.event.resolve']);
    }

    public function test_request_id_middleware_sets_header(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/activity-logs');

        $response->assertHeader('X-Request-Id');
        $this->assertNotNull($response->headers->get('X-Request-Id'));
    }

    public function test_guest_is_redirected_from_activity_logs(): void
    {
        $this->get('/admin/activity-logs')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_view_activity_logs(): void
    {
        $unauthorized = User::factory()->create();
        $this->actingAs($unauthorized);

        $this->get('/admin/activity-logs')->assertForbidden();
    }

    public function test_activity_log_is_recorded_and_visible(): void
    {
        app(ActivityLogger::class)->log('USER_VIEWED', 'User opened patient list', properties: ['context' => 'patients']);

        $this->assertDatabaseCount('activity_logs', 1);

        $this->actingAs($this->admin)
            ->get('/admin/activity-logs')
            ->assertOk()
            ->assertSee('USER_VIEWED');
    }

    public function test_activity_log_show_page(): void
    {
        $log = app(ActivityLogger::class)->log('USER_CREATED', 'User created', properties: ['email' => 'test@example.com']);

        $this->actingAs($this->admin)
            ->get('/admin/activity-logs/'.$log->id)
            ->assertOk()
            ->assertSee('USER_CREATED');
    }

    public function test_security_event_is_recorded_and_resolved(): void
    {
        app(SecurityLogger::class)->log('LOGIN_FAILED', ['email' => 'bad@example.com'], SecurityLogger::WARNING);

        $this->assertDatabaseHas('security_events', ['event' => 'LOGIN_FAILED', 'severity' => 'warning']);

        $event = SecurityEvent::first();

        $this->actingAs($this->admin)
            ->get('/admin/security-events')
            ->assertOk()
            ->assertSee('LOGIN_FAILED');

        $this->actingAs($this->admin)
            ->get('/admin/security-events/'.$event->id)
            ->assertOk();

        $this->actingAs($this->admin)
            ->put('/admin/security-events/'.$event->id.'/resolve', ['resolution_note' => 'Reviewed; false positive.'])
            ->assertRedirect();

        $this->assertNotNull($event->fresh()->resolved_at);
        $this->assertDatabaseHas('security_events', ['id' => $event->id]);
    }

    public function test_security_event_resolve_requires_permission(): void
    {
        $event = app(SecurityLogger::class)->log('PASSWORD_CHANGED');

        $unauthorized = User::factory()->create();
        $this->actingAs($unauthorized);

        $this->put('/admin/security-events/'.$event->id.'/resolve', ['resolution_note' => 'x'])
            ->assertForbidden();
    }
}
