<?php

use App\Models\Patient;
use App\Services\Search\PatientSearchIndexer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global search index (guideline 14.5, H-09). One row per searchable term, so every lookup is an indexed prefix or exact match
 * (no `%term%` table scans) and the term order of a name does not matter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_search_index', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('kind', 12);          // name, phonetic, phone, nid, mrn
            $table->string('term', 64);

            $table->index(['organization_id', 'kind', 'term'], 'idx_patient_search_lookup');
            $table->index('patient_id', 'idx_patient_search_patient');
        });

        $indexer = app(PatientSearchIndexer::class);
        Patient::withoutGlobalScopes()->withTrashed()->orderBy('id')->chunkById(500, fn ($patients) => $patients->each(fn ($p) => $indexer->reindex($p)));
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_search_index');
    }
};
