<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_pacs_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            // orthanc|null — vendor-neutral adapter selection (Phase 6 plan decision #2).
            // Never hardcode a vendor into the domain layer; add adapters here as needed.
            $table->string('adapter_type')->default('null');
            $table->string('base_url')->nullable();
            $table->string('ae_title')->nullable();
            $table->unsignedInteger('port')->nullable();
            // Credentials are encrypted at rest (Eloquent `encrypted` cast) — never stored in
            // source or .env (spec §11/§58/§92).
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_pacs_servers');
    }
};
