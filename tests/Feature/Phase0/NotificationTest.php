<?php

namespace Tests\Feature\Phase0;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'notification.view', 'guard_name' => 'web']);

        $this->recipient = User::factory()->create(['password' => 'password']);
        $this->recipient->givePermissionTo('notification.view');
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $this->get('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_user_can_list_own_notifications(): void
    {
        app(NotificationService::class)->send(
            $this->recipient,
            'security_alert',
            ['device' => 'Chrome on macOS'],
            ['database']
        );

        Sanctum::actingAs($this->recipient);

        $this->get('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.unread', 1)
            ->assertJsonPath('data.0.title', 'Security Alert')
            ->assertJsonPath('data.0.message', 'A new sign-in was detected for your account on Chrome on macOS.');
    }

    public function test_mark_read_endpoint_updates_notification(): void
    {
        app(NotificationService::class)->send($this->recipient, 'system_maintenance', ['time' => '02:00']);

        Sanctum::actingAs($this->recipient);

        $notificationId = Notification::where('user_id', $this->recipient->id)->value('id');

        $this->patch('/api/v1/notifications/'.$notificationId.'/read')
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'is_read' => true,
        ]);

        $this->assertSame(0, app(NotificationService::class)->unreadForUser($this->recipient)->count());
    }

    public function test_user_cannot_read_another_users_notification(): void
    {
        app(NotificationService::class)->send($this->recipient, 'password_reset', ['token' => 'abc']);

        $other = User::factory()->create();
        $other->givePermissionTo('notification.view');
        Sanctum::actingAs($other);

        $notificationId = Notification::where('user_id', $this->recipient->id)->value('id');

        $this->patch('/api/v1/notifications/'.$notificationId.'/read')
            ->assertForbidden();
    }
}
