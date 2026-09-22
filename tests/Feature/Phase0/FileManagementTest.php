<?php

namespace Tests\Feature\Phase0;

use App\Models\User;
use App\Services\FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FileManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['file.view', 'file.manage'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->owner = User::factory()->create(['password' => 'password']);
        $this->owner->givePermissionTo(['file.view', 'file.manage']);
    }

    public function test_files_are_private_by_default_and_metadata_stored(): void
    {
        Storage::fake('local');

        $uploaded = UploadedFile::fake()->image('report.jpg', 100, 100)->mimeType('image/jpeg');

        $file = app(FileService::class)->store($uploaded, null, $this->owner, 'local');

        $this->assertDatabaseHas('files', [
            'original_name' => 'report.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'uploaded_by' => $this->owner->id,
            'is_public' => false,
        ]);

        $this->assertFalse($file->fresh()->is_public);
    }

    public function test_owner_can_list_files(): void
    {
        Storage::fake('local');

        app(FileService::class)->store(UploadedFile::fake()->image('a.jpg'), null, $this->owner, 'local');

        $this->actingAs($this->owner)
            ->get('/admin/files')
            ->assertOk()
            ->assertSee('a.jpg');
    }

    public function test_download_is_authorized_for_owner(): void
    {
        Storage::fake('local');

        $file = app(FileService::class)->store(UploadedFile::fake()->image('doc.jpg'), null, $this->owner, 'local');

        $this->actingAs($this->owner)
            ->get('/admin/files/'.$file->id)
            ->assertOk()
            ->assertSee($file->original_name);
    }

    public function test_unauthorized_user_cannot_access_other_users_file(): void
    {
        Storage::fake('local');

        $file = app(FileService::class)->store(UploadedFile::fake()->image('secret.jpg'), null, $this->owner, 'local');

        $other = User::factory()->create();
        $this->actingAs($other);

        $this->get('/admin/files/'.$file->id)->assertForbidden();
    }

    public function test_owner_can_delete_file(): void
    {
        Storage::fake('local');

        $file = app(FileService::class)->store(UploadedFile::fake()->image('temp.jpg'), null, $this->owner, 'local');

        $this->actingAs($this->owner)
            ->delete('/admin/files/'.$file->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }
}
