<?php

namespace Tests\Feature\Auth;

use App\Models\LoginHistory;
use App\Models\User;
use App\Services\SecurityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_records_login_history_and_updates_user(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

        $response->assertRedirect('/login/context');
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => 'success',
        ]);

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_failed_login_increments_attempts_and_records_history(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $user->refresh();
        $this->assertSame(1, $user->failed_login_attempts);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'status' => 'failed',
            'failure_reason' => 'invalid_credentials',
        ]);
    }

    public function test_account_locks_after_max_failed_attempts(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        for ($i = 0; $i < User::MAX_FAILED_LOGIN_ATTEMPTS; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $user->refresh();
        $this->assertTrue($user->isLocked());
        $this->assertSame(User::MAX_FAILED_LOGIN_ATTEMPTS, $user->failed_login_attempts);

        $this->assertDatabaseHas('security_events', [
            'user_id' => $user->id,
            'event' => 'ACCOUNT_LOCKED',
            'severity' => SecurityLogger::CRITICAL,
        ]);
    }

    public function test_locked_account_is_rejected_even_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
            'locked_until' => now()->addMinutes(15),
        ]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'status' => 'failed',
            'failure_reason' => 'account_locked',
        ]);
    }

    public function test_inactive_account_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_successful_login_resets_previous_failed_attempts(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
            'failed_login_attempts' => 3,
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
    }

    public function test_logout_marks_login_history_logged_out_at(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

        $history = LoginHistory::where('user_id', $user->id)->where('status', 'success')->firstOrFail();
        $this->assertNull($history->logged_out_at);

        $this->post('/logout');

        $history->refresh();
        $this->assertNotNull($history->logged_out_at);
    }
}
