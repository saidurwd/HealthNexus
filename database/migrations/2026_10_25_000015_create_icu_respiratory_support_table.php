<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_respiratory_support', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('device');
            $table->decimal('flow_rate', 6, 2)->nullable();
            $table->decimal('fio2', 5, 2)->nullable();
            $table->text('reason')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('stopped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_respiratory_support');
    }
};
