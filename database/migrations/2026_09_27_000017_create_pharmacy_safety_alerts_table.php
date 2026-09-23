<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_safety_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('pharmacy_order_items')->cascadeOnDelete();
            $table->foreignId('dispensing_item_id')->nullable()->constrained('pharmacy_dispensing_items')->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->string('alert_type');
            $table->string('severity')->default('moderate');
            $table->string('interacting_reference')->nullable();
            $table->text('explanation');
            $table->text('recommended_action')->nullable();
            $table->boolean('is_overridden')->default(false);
            $table->text('override_reason')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'is_overridden']);
            $table->index('order_item_id');
            $table->index('dispensing_item_id');
            $table->index('medication_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_safety_alerts');
    }
};
