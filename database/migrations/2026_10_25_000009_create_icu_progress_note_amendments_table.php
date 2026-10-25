<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_progress_note_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('icu_progress_notes')->cascadeOnDelete();
            $table->string('amendment_type')->default('addendum');
            $table->text('reason')->nullable();
            $table->text('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_progress_note_amendments');
    }
};
