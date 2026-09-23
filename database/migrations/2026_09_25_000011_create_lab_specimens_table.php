<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_specimens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->foreignId('specimen_type_id')->nullable()->constrained('lab_specimen_types')->nullOnDelete();
            $table->foreignId('container_type_id')->nullable()->constrained('lab_container_types')->nullOnDelete();
            $table->string('accession_number');
            $table->string('barcode')->nullable();
            // pending|collected|in_transit|received|accepted|rejected|processing|completed
            $table->string('status')->default('pending');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('storage_location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'accession_number']);
            $table->unique(['company_id', 'barcode']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['lab_order_id']);
        });

        Schema::table('lab_order_items', function (Blueprint $table) {
            $table->foreignId('specimen_id')->nullable()->after('panel_id')->constrained('lab_specimens')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lab_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specimen_id');
        });

        Schema::dropIfExists('lab_specimens');
    }
};
