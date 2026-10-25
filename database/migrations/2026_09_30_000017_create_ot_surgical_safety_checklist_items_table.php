<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_surgical_safety_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('ot_surgical_safety_checklists')->cascadeOnDelete();
            $table->string('label');
            $table->string('response')->nullable();
            $table->boolean('is_required')->default(true);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('exception_reason')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('checklist_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_surgical_safety_checklist_items');
    }
};
