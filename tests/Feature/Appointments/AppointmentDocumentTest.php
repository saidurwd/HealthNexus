<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppointmentDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('local');

        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $this->user = User::factory()->create();
        $this->user->companies()->attach($company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $company->id]);

        foreach (['appointments.view', 'appointments.update'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $company->id);
        app(TenantContextResolver::class)->setCompanyId($company->id);

        $patient = Patient::factory()->create(['company_id' => $company->id]);
        $this->appointment = Appointment::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id, 'patient_id' => $patient->id]);
    }

    public function test_can_upload_a_document(): void
    {
        $file = UploadedFile::fake()->create('referral.pdf', 100, 'application/pdf');

        $response = $this->post("/admin/appointments/{$this->appointment->id}/documents", [
            'document' => $file,
            'document_type' => 'referral_letter',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointment_documents', [
            'appointment_id' => $this->appointment->id,
            'document_type' => 'referral_letter',
        ]);

        $document = $this->appointment->documents()->first();
        Storage::disk('local')->assertExists($document->file->path);
    }

    public function test_can_download_an_uploaded_document(): void
    {
        $file = UploadedFile::fake()->create('confirmation.pdf', 50, 'application/pdf');
        $this->post("/admin/appointments/{$this->appointment->id}/documents", [
            'document' => $file, 'document_type' => 'booking_confirmation',
        ]);

        $document = $this->appointment->documents()->first();

        $this->get("/admin/appointments/{$this->appointment->id}/documents/{$document->id}/download")->assertOk();
    }

    public function test_can_delete_a_document(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');
        $this->post("/admin/appointments/{$this->appointment->id}/documents", [
            'document' => $file, 'document_type' => 'supporting_document',
        ]);

        $document = $this->appointment->documents()->first();
        $path = $document->file->path;

        $this->delete("/admin/appointments/{$this->appointment->id}/documents/{$document->id}")->assertRedirect();

        $this->assertDatabaseMissing('appointment_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
    }
}
