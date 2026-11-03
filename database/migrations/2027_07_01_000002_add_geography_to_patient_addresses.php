<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Additive: an address can point at the division, district, upazila and union it is in, and say the village or neighbourhood. The older free-text district stays and is kept filled. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_addresses', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('state_id')->constrained('geo_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('division_id')->constrained('geo_districts')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->after('district_id')->constrained('geo_upazilas')->nullOnDelete();
            $table->foreignId('union_id')->nullable()->after('upazila_id')->constrained('geo_unions')->nullOnDelete();
            $table->string('village_area', 120)->nullable()->after('union_id');

            $table->index(['organization_id', 'district_id'], 'patient_address_district_idx');
        });
    }

    public function down(): void
    {
        Schema::table('patient_addresses', function (Blueprint $table) {
            $table->dropIndex('patient_address_district_idx');
            $table->dropConstrainedForeignId('union_id');
            $table->dropConstrainedForeignId('upazila_id');
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('division_id');
            $table->dropColumn('village_area');
        });
    }
};
