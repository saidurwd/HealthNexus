<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_infusion_titrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infusion_id')->constrained('icu_infusions')->cascadeOnDelete();
            $table->decimal('previous_rate', 10, 3)->nullable();
            $table->decimal('new_rate', 10, 3);
            $table->string('rate_unit')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['infusion_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_infusion_titrations');
    }
};
