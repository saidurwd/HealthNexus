<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A step is satisfied by ANY ONE of its approver rows acting — this is what gives a single
     * "sequential" step flexible eligibility ("any billing supervisor", or "Dr. Rahman
     * specifically") without needing full parallel/quorum approval semantics.
     */
    public function up(): void
    {
        Schema::create('workflow_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
            $table->string('approver_type'); // role | user | department
            $table->string('role_name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['workflow_step_id', 'approver_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approvers');
    }
};
