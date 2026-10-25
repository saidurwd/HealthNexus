<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipd_wards', function (Blueprint $table) {
            $table->string('ward_type')->default('general')->after('name');
            $table->index(['organization_id', 'branch_id', 'ward_type']);
        });
    }

    public function down(): void
    {
        Schema::table('ipd_wards', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'branch_id', 'ward_type']);
            $table->dropColumn('ward_type');
        });
    }
};
