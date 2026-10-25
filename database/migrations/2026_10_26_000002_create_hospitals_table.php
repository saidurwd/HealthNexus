<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Organization → Hospital → Branch (guideline D-02). A hospital is one licensed facility.
 * Every existing organization gets one default hospital that owns all of its branches.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('slug');
            $table->string('license_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->unique(['organization_id', 'slug']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('hospital_id')->nullable()->after('organization_id')->constrained()->restrictOnDelete();
        });

        DB::table('organizations')->orderBy('id')->each(function ($organization) {
            $hospitalId = DB::table('hospitals')->insertGetId([
                'organization_id' => $organization->id,
                'name' => $organization->name,
                'code' => 'MAIN',
                'slug' => 'main',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('branches')->where('organization_id', $organization->id)->update(['hospital_id' => $hospitalId]);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hospital_id');
        });

        Schema::dropIfExists('hospitals');
    }
};
