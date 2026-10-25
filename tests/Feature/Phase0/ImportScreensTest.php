<?php

namespace Tests\Feature\Phase0;

use App\Models\Branch;
use App\Models\ImportBatch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ImportScreensTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->organization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user = $this->member($this->organization, ['import.view', 'import.patients']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        $this->actingAs($this->user);
        session()->put('tenant_organization_id', $this->organization->id);
        session()->put('tenant_branch_id', $branch->id);
    }

    private function member(Organization $organization, array $permissions): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    public function test_upload_preview_import_error_file_and_rollback_through_the_screens(): void
    {
        $this->get('/admin/imports')->assertOk()->assertSee('Patients')->assertDontSee('Staff accounts');
        $this->get('/admin/imports/template/patients')->assertOk();

        $file = UploadedFile::fake()->createWithContent('patients.csv', "first_name,last_name,phone\nRahim,Uddin,01712345678\n,Broken,01712345679\n");
        $this->post('/admin/imports', ['import' => 'patients', 'file' => $file])->assertRedirect();

        $batch = ImportBatch::firstOrFail();
        $this->assertSame('validated', $batch->status, 'the queue runs synchronously in tests');
        $this->get(route('admin.imports.show', $batch))->assertOk()->assertSee('first_name is required');

        $this->post(route('admin.imports.commit', $batch))->assertRedirect();
        $this->assertSame(1, Patient::where('organization_id', $this->organization->id)->count());
        $this->get(route('admin.imports.errors', $batch))->assertOk();

        $this->post(route('admin.imports.rollback', $batch))->assertRedirect();
        $this->assertSame(0, Patient::where('organization_id', $this->organization->id)->count());
    }

    public function test_other_organizations_batches_are_404_and_missing_permission_is_403(): void
    {
        $other = Organization::factory()->create();
        $batch = ImportBatch::create([
            'organization_id' => $other->id, 'user_id' => $this->member($other, [])->id, 'import_key' => 'patients', 'original_name' => 'x.csv',
        ]);

        $this->get(route('admin.imports.show', $batch))->assertNotFound();

        $this->actingAs($this->member($this->organization, []));
        $this->get('/admin/imports')->assertForbidden();
        $this->post('/admin/imports', ['import' => 'patients', 'file' => UploadedFile::fake()->createWithContent('p.csv', "first_name,last_name\nA,B\n")])->assertForbidden();
    }
}
