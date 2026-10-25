<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only record of a dose given in the department. A wrong entry is answered by a new note, never an edit.
        Schema::create('ed_medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('ed_visits')->cascadeOnDelete();
            $table->string('drug_name', 200);
            $table->string('dose', 100);
            $table->string('route', 50);
            $table->boolean('is_verbal_order')->default(false);
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamp('given_at');
            $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['visit_id', 'given_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ed_medication_administrations');
    }
};
