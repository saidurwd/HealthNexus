<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression coverage for a confirmed privacy bug: patient documents were being uploaded to the
 * PUBLIC disk (`$file->store('patient-documents', 'public')`) and the model's url() accessor
 * built a raw, unauthenticated `asset('storage/...')` URL — meaning anyone with the link could
 * view a private patient document with no login or company check at all. Both are now fixed:
 * uploads go to the private "local" disk, and the url() accessor routes through an authorized
 * download action instead.
 */
class PatientDocumentPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('local');
        Storage::fake('public');

        $this->company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        foreach (['manage companies', 'patients.update'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $this->company->id);
        app(TenantContextResolver::class)->setCompanyId($this->company->id);

        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_uploaded_document_is_stored_on_the_private_disk_not_public(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $this->post("/admin/patients/{$this->patient->id}/documents", [
            'document' => $file,
            'document_type' => 'lab_report',
        ])->assertRedirect();

        $document = PatientDocument::where('patient_id', $this->patient->id)->firstOrFail();

        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
    }

    public function test_document_url_accessor_points_at_the_authorized_download_route_not_a_raw_storage_path(): void
    {
        $document = PatientDocument::factory()->create([
            'company_id' => $this->company->id,
            'patient_id' => $this->patient->id,
        ]);

        $this->assertStringContainsString(
            "/admin/patients/{$this->patient->id}/documents/{$document->id}/download",
            $document->url
        );
        $this->assertStringNotContainsString('/storage/', $document->url);
    }

    public function test_download_requires_view_access_to_the_patient(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create();
        $otherUser->companies()->attach($otherCompany->id, ['access_level' => 'admin']);
        $this->actingAs($otherUser);

        Storage::disk('local')->put('patient-documents/secret.pdf', 'contents');
        $document = PatientDocument::factory()->create([
            'company_id' => $this->company->id,
            'patient_id' => $this->patient->id,
            'file_path' => 'patient-documents/secret.pdf',
        ]);

        $this->get("/admin/patients/{$this->patient->id}/documents/{$document->id}/download")
            ->assertForbidden();
    }

    public function test_authorized_user_can_download_the_document(): void
    {
        Storage::disk('local')->put('patient-documents/report.pdf', 'the contents');
        $document = PatientDocument::factory()->create([
            'company_id' => $this->company->id,
            'patient_id' => $this->patient->id,
            'file_path' => 'patient-documents/report.pdf',
        ]);

        $this->get("/admin/patients/{$this->patient->id}/documents/{$document->id}/download")
            ->assertOk();
    }

    public function test_deleting_a_document_removes_the_underlying_file(): void
    {
        Storage::disk('local')->put('patient-documents/to-delete.pdf', 'contents');
        $document = PatientDocument::factory()->create([
            'company_id' => $this->company->id,
            'patient_id' => $this->patient->id,
            'file_path' => 'patient-documents/to-delete.pdf',
        ]);

        $this->delete("/admin/patients/{$this->patient->id}/documents/{$document->id}")
            ->assertRedirect();

        Storage::disk('local')->assertMissing('patient-documents/to-delete.pdf');
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('script.php', 10, 'application/x-php');

        $response = $this->post("/admin/patients/{$this->patient->id}/documents", [
            'document' => $file,
            'document_type' => 'lab_report',
        ]);

        $response->assertSessionHasErrors('document');
        $this->assertDatabaseMissing('patient_documents', ['patient_id' => $this->patient->id]);
    }
}
