<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Default items are seeded from the ot.preop_checklist_items setting (hospital-editable) —
     * never hardcoded in a controller, per spec §19/§41.
     */
    public function up(): void
    {
        Schema::create('ot_preoperative_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('ot_preoperative_checklists')->cascadeOnDelete();
            $table->string('category');
            $table->string('label');
            $table->string('response')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_applicable')->default(true);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('exception_reason')->nullable();
            $table->timestamps();

            $table->index(['checklist_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_preoperative_checklist_items');
    }
};
