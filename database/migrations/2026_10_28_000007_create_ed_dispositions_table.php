<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per visit, enforced by the unique key: a visit leaves the department exactly once.
        Schema::create('ed_dispositions', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->unique()->constrained('ed_visits')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('destination', 200)->nullable();
            $table->text('reason')->nullable();
            $table->boolean('ambulance_required')->default(false);
            $table->json('details')->nullable();
            $table->string('target_type', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamp('decided_at');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'type', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ed_dispositions');
    }
};
