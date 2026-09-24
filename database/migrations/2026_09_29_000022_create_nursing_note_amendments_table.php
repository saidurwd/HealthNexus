<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_note_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('nursing_notes')->cascadeOnDelete();
            $table->string('amendment_type')->default('addendum');
            $table->text('reason');
            $table->text('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_note_amendments');
    }
};
