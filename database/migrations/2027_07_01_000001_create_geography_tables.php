<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The country's administrative geography: division > district > upazila > union. National reference data, shared by every hospital (not scoped to an organization). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 80)->unique();
            $table->string('name_bn', 80);
            $table->string('code', 10)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('geo_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('geo_divisions')->cascadeOnDelete();
            $table->string('name_en', 80);
            $table->string('name_bn', 80);
            $table->string('code', 10)->nullable();
            $table->timestamps();

            $table->unique(['division_id', 'name_en']);
        });

        Schema::create('geo_upazilas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('geo_districts')->cascadeOnDelete();
            $table->string('name_en', 80);
            $table->string('name_bn', 80);
            $table->string('code', 10)->nullable();
            $table->timestamps();

            $table->unique(['district_id', 'name_en']);
        });

        // Unions have no source data yet: the hospital adds the ones its patients come from, or imports the national list (geo:import-unions).
        Schema::create('geo_unions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upazila_id')->constrained('geo_upazilas')->cascadeOnDelete();
            $table->string('name_en', 80);
            $table->string('name_bn', 80)->nullable();
            $table->string('code', 12)->nullable();
            $table->string('source', 10)->default('manual'); // manual | import
            $table->timestamps();

            $table->unique(['upazila_id', 'name_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_unions');
        Schema::dropIfExists('geo_upazilas');
        Schema::dropIfExists('geo_districts');
        Schema::dropIfExists('geo_divisions');
    }
};
