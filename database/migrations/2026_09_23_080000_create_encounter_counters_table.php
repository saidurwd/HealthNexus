<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->integer('counter')->default(0);
            $table->timestamps();

            $table->unique(['organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_counters');
    }
};
