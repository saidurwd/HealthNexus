<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_cashier_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('open');
            $table->string('currency', 3)->default('BDT');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('expected_collections', 18, 2)->default(0);
            $table->decimal('expected_refunds', 18, 2)->default(0);
            $table->decimal('expected_closing', 18, 2)->default(0);
            $table->decimal('actual_closing', 18, 2)->default(0);
            $table->decimal('variance', 18, 2)->default(0);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'user_id', 'status'], 'billing_cashier_sessions_scope_idx');
            $table->index(['opened_at', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_cashier_sessions');
    }
};
