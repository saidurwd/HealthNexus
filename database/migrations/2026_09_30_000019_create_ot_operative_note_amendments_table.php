<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only corrections — mirrors NursingNoteAmendment exactly: addendum only, the original
     * finalized note is never silently edited (spec §60).
     */
    public function up(): void
    {
        Schema::create('ot_operative_note_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operative_note_id')->constrained('ot_operative_notes')->cascadeOnDelete();
            $table->string('amendment_type')->default('addendum');
            $table->text('reason');
            $table->text('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('operative_note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_operative_note_amendments');
    }
};
