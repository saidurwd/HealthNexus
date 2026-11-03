<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Numbering Service storage (guideline 14.2 / H-02). hospital_id and branch_id are NOT NULL with 0 meaning
 * "not scoped at this level", so the unique key works on MySQL (where NULLs never collide).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('hospital_id')->default(0);
            $table->unsignedBigInteger('branch_id')->default(0);
            $table->string('series', 40);
            $table->string('period_key', 12)->default('');
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'hospital_id', 'branch_id', 'series', 'period_key'], 'uq_number_series_scope');
        });

        // Append-only audit of every number handed out. Gaps are visible here, never silent.
        Schema::create('number_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('number_series_id')->constrained('number_series')->restrictOnDelete();
            $table->unsignedBigInteger('value');
            $table->string('number', 64);
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('allocated_at')->useCurrent();

            $table->unique(['number_series_id', 'value'], 'uq_number_allocations_value');
        });

        // Billing is the first module on the shared service: carry its counters over so numbers continue.
        if (Schema::hasTable('billing_counters')) {
            DB::table('billing_counters')
                ->select('organization_id', 'branch_id', 'document_type', DB::raw('max(last_number) as last_number'))
                ->groupBy('organization_id', 'branch_id', 'document_type')
                ->get()
                ->each(fn ($row) => DB::table('number_series')->insert([
                    'organization_id' => $row->organization_id,
                    'hospital_id' => 0,
                    'branch_id' => $row->branch_id ?? 0,
                    'series' => 'billing.'.strtolower($row->document_type),
                    'period_key' => '',
                    'last_value' => $row->last_number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('number_allocations');
        Schema::dropIfExists('number_series');
    }
};
