<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Permission::create(['name' => 'manage companies', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage users', 'guard_name' => 'web']);
        $this->user->givePermissionTo('manage companies');
        $this->user->givePermissionTo('manage users');

        $this->actingAs($this->user);
    }

    public function test_user_can_upload_profile_picture_on_create(): void
    {
        Storage::fake('public');

        $this->mock(FileUploadService::class, function ($mock) {
            $mock->shouldReceive('upload')->andReturn('profile-pictures/test-profile.jpg');
            $mock->shouldReceive('validate')->andReturn(null);
            $mock->shouldReceive('scanForMalware')->andReturn(false);
        });

        $response = $this->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '1234567890',
            'profile_picture' => UploadedFile::fake()->image('profile.jpg', 200, 200),
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals('profile-pictures/test-profile.jpg', $user->profile_picture);
    }

    public function test_user_can_update_profile_picture(): void
    {
        Storage::fake('public');

        $this->mock(FileUploadService::class, function ($mock) {
            $mock->shouldReceive('upload')->andReturn('profile-pictures/new-profile.jpg');
            $mock->shouldReceive('validate')->andReturn(null);
            $mock->shouldReceive('scanForMalware')->andReturn(false);
        });

        $response = $this->put('/admin/users/'.$this->user->id, [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => '9876543210',
            'profile_picture' => UploadedFile::fake()->image('new-profile.jpg', 200, 200),
        ]);

        $response->assertRedirect('/admin/users');

        $this->user->refresh();
        $this->assertEquals('profile-pictures/new-profile.jpg', $this->user->profile_picture);
    }

    public function test_user_can_view_profile_picture_on_show_page(): void
    {
        $this->user->update([
            'profile_picture' => 'profile-pictures/test.jpg',
        ]);

        $response = $this->get(route('admin.users.show', $this->user));

        $response->assertStatus(200)
            ->assertSee('profile-pictures/test.jpg');
    }

    public function test_user_show_page_shows_placeholder_without_profile_picture(): void
    {
        $this->user->update([
            'profile_picture' => null,
        ]);

        $response = $this->get(route('admin.users.show', $this->user));

        $response->assertStatus(200)
            ->assertSee($this->user->name[0]);
    }
}
