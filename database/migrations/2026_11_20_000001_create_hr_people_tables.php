<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 16, part 1: the employee master, its history, jobs and positions, contracts, documents, credentials, onboarding / offboarding, recruitment (S01–S03). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('title', 150);
            $table->string('grade', 20)->nullable();
            $table->string('family', 14)->default('admin'); // clinical | nursing | allied | admin | support
            $table->json('required_credentials')->nullable(); // credential types a person in this job must hold, e.g. ["bmdc"]
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('hr_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('hr_jobs')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30);
            $table->string('title', 150);
            $table->unsignedSmallInteger('headcount')->default(1);
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        // Personal and payroll-sensitive columns (national id, tax id, bank and wallet numbers) are stored encrypted (class C3/C4).
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('hr_jobs')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('hr_positions')->nullOnDelete();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('employee_no', 30);
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('name_bn', 150)->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('marital_status', 12)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 300)->nullable();
            $table->text('national_id')->nullable();   // encrypted
            $table->text('tin')->nullable();           // encrypted
            $table->string('bank_name', 100)->nullable();
            $table->text('bank_account')->nullable();  // encrypted
            $table->text('mobile_wallet')->nullable(); // encrypted
            $table->string('category', 14)->default('permanent'); // permanent | contract | part_time | visiting | intern | casual
            $table->string('status', 14)->default('onboarding'); // onboarding | active | on_leave | suspended | resigned | terminated | retired
            $table->date('joined_on')->nullable();
            $table->date('probation_ends_on')->nullable();
            $table->date('confirmed_on')->nullable();
            $table->date('separated_on')->nullable();
            $table->string('separation_reason', 300)->nullable();
            $table->string('weekly_off', 3)->default('fri');
            $table->string('device_code', 40)->nullable(); // the number the biometric device knows this person by
            $table->string('emergency_contact', 150)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'employee_no']);
            $table->unique(['organization_id', 'device_code'], 'uq_hr_employee_device');
            $table->unique('user_id');
            $table->index(['organization_id', 'status', 'department_id'], 'idx_hr_employee_status');
        });

        // Append-only: what changed, from what to what, effective when, who did it and why.
        Schema::create('hr_employee_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('change_type', 16); // hired | transfer | promotion | status | manager | category | job | separation | rehire | correction
            $table->json('from_value')->nullable();
            $table->json('to_value')->nullable();
            $table->date('effective_on');
            $table->string('reason', 300)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['employee_id', 'effective_on']);
        });

        // D-12: a provider is a role a person plays; the employee is the person employed. The link is proposed, then a person decides.
        Schema::create('hr_provider_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('hr_employees')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('reasons', 300)->nullable();
            $table->string('status', 10)->default('pending'); // pending | accepted | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'employee_id'], 'uq_hr_provider_link');
        });

        Schema::create('hr_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('contract_type', 12)->default('permanent'); // permanent | fixed_term | probation | visiting
            $table->date('start_on');
            $table->date('end_on')->nullable();
            $table->unsignedSmallInteger('notice_days')->default(30);
            $table->unsignedTinyInteger('probation_months')->default(0);
            $table->string('status', 10)->default('active'); // active | ended | renewed
            $table->string('terms', 300)->nullable();
            $table->unsignedBigInteger('supersedes_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('end_on');
        });

        Schema::create('hr_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('doc_type', 20); // nid | cv | photo | certificate | offer_letter | contract | other
            $table->string('title', 150);
            $table->string('number', 80)->nullable();
            $table->date('expires_on')->nullable();
            $table->unsignedBigInteger('file_id')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'doc_type']);
        });

        Schema::create('hr_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('credential_type', 20); // bmdc | bnmc | pharmacy_council | dental_council | bls | acls | other
            $table->string('number', 80)->nullable();
            $table->string('issuing_body', 120)->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('status', 10)->default('active'); // active | expired | suspended | revoked
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'credential_type']);
            $table->index('expires_on');
        });

        Schema::create('hr_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('kind', 12); // onboarding | offboarding
            $table->string('status', 8)->default('open'); // open | done
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'kind']);
        });

        Schema::create('hr_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('hr_checklists')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('title', 200);
            $table->string('owner_role', 40)->nullable();
            $table->boolean('required')->default(true);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('hr_vacancies', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('hr_positions')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->unsignedSmallInteger('openings')->default(1);
            $table->string('status', 8)->default('open'); // draft | open | filled | closed
            $table->date('opened_on')->nullable();
            $table->date('closes_on')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vacancy_id')->constrained('hr_vacancies')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('phone', 40)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('stage', 10)->default('applied'); // applied | screened | interview | offered | hired | rejected | withdrawn
            $table->string('notes', 300)->nullable();
            $table->unsignedBigInteger('hired_employee_id')->nullable();
            $table->timestamps();
        });

        // providers gain the link to the person employed (D-12). Plain column: HR is the master for name, licence and status.
        Schema::table('providers', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('user_id');
            $table->index('employee_id', 'idx_providers_employee');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropIndex('idx_providers_employee');
            $table->dropColumn('employee_id');
        });
        foreach (['hr_candidates', 'hr_vacancies', 'hr_checklist_items', 'hr_checklists', 'hr_credentials', 'hr_documents', 'hr_contracts', 'hr_provider_links', 'hr_employee_history', 'hr_employees', 'hr_positions', 'hr_jobs'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
