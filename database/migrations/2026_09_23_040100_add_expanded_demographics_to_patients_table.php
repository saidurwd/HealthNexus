<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('patient_type_id')->nullable()->after('company_id')->constrained('patient_types')->nullOnDelete();
            $table->foreignId('gender_id')->nullable()->after('sex')->constrained('genders')->nullOnDelete();
            $table->foreignId('marital_status_id')->nullable()->after('gender_id')->constrained('marital_statuses')->nullOnDelete();
            $table->foreignId('nationality_id')->nullable()->after('marital_status_id')->constrained('countries')->nullOnDelete();
            $table->string('preferred_name')->nullable()->after('last_name');
            $table->string('display_name')->nullable()->after('preferred_name');
            $table->boolean('dob_unknown')->default(false)->after('date_of_birth');
            $table->unsignedSmallInteger('estimated_age')->nullable()->after('dob_unknown');
            $table->enum('estimated_age_unit', ['years', 'months', 'days'])->nullable()->after('estimated_age');
            $table->enum('rh_factor', ['positive', 'negative', 'unknown'])->nullable()->after('blood_group');
            $table->timestamp('deceased_at')->nullable()->after('rh_factor');
            $table->boolean('is_temporary')->default(false)->after('deceased_at');
            $table->boolean('is_unknown')->default(false)->after('is_temporary');
            $table->timestamp('registered_at')->nullable()->after('is_unknown');
            $table->foreignId('registered_by')->nullable()->after('registered_at')->constrained('users')->nullOnDelete();
            $table->foreignId('photo_file_id')->nullable()->after('registered_by')->constrained('files')->nullOnDelete();
            $table->boolean('portal_enabled')->default(false)->after('photo_file_id');
            $table->foreignId('portal_user_id')->nullable()->after('portal_enabled')->constrained('users')->nullOnDelete();

            $table->index(['company_id', 'patient_type_id'], 'patients_company_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_company_type_idx');
            $table->dropConstrainedForeignId('patient_type_id');
            $table->dropConstrainedForeignId('gender_id');
            $table->dropConstrainedForeignId('marital_status_id');
            $table->dropConstrainedForeignId('nationality_id');
            $table->dropConstrainedForeignId('registered_by');
            $table->dropConstrainedForeignId('photo_file_id');
            $table->dropConstrainedForeignId('portal_user_id');
            $table->dropColumn([
                'preferred_name', 'display_name', 'dob_unknown', 'estimated_age', 'estimated_age_unit',
                'rh_factor', 'deceased_at', 'is_temporary', 'is_unknown', 'registered_at', 'portal_enabled',
            ]);
        });
    }
};
