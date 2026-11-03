<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_family_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->timestamp('communicated_at');
            $table->string('family_contact')->nullable();
            $table->string('relationship')->nullable();
            $table->string('communication_type')->nullable();
            $table->text('participants')->nullable();
            $table->text('clinical_information_shared')->nullable();
            $table->text('treatment_discussion')->nullable();
            $table->text('prognosis_discussion')->nullable();
            $table->text('questions')->nullable();
            $table->text('decisions')->nullable();
            $table->text('follow_up')->nullable();
            $table->foreignId('documented_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'communicated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_family_communications');
    }
};
