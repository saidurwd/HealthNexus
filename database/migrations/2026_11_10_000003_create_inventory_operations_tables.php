<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 15, part 3: transfers, internal requests and issues, consumption, counts, adjustments, recalls (S08, S09, S12). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_store_id')->constrained('inventory_stores')->restrictOnDelete();
            $table->foreignId('to_store_id')->constrained('inventory_stores')->restrictOnDelete();
            $table->string('number', 40);
            $table->string('status', 12)->default('requested'); // requested | dispatched | received | cancelled
            $table->string('notes', 300)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('inventory_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('inventory_transfers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->unsignedBigInteger('batch_id')->default(0);
            $table->decimal('quantity', 14, 4);          // asked for, then what left the store
            $table->decimal('received_quantity', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->restrictOnDelete(); // the store asked to supply
            $table->string('number', 40);
            $table->string('status', 16)->default('submitted'); // submitted | partially_issued | issued | rejected | cancelled
            $table->string('notes', 300)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('inventory_request_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('inventory_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('issued_quantity', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_issues', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('request_id')->nullable();
            $table->string('number', 40);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['department_id', 'issued_at']);
        });

        Schema::create('inventory_issue_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('inventory_issues')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->unsignedBigInteger('batch_id')->default(0);
            $table->decimal('quantity', 14, 4);
            $table->decimal('returned_quantity', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->timestamps();
        });

        // Use at the point of care. A patient-linked, chargeable item also announces a ChargeEvent (Inventory never prices).
        Schema::create('inventory_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->unsignedBigInteger('batch_id')->default(0);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('encounter_id')->nullable();
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->string('source_module', 20)->default('inventory');
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->string('charge_key', 160)->nullable();
            $table->foreignId('consumed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consumed_at');
            $table->timestamps();

            $table->index(['organization_id', 'consumed_at'], 'idx_inv_consumption_time');
            $table->index(['department_id', 'consumed_at'], 'idx_inv_consumption_dept');
        });

        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->restrictOnDelete();
            $table->string('number', 40);
            $table->string('kind', 8)->default('cycle'); // full | cycle
            $table->string('status', 12)->default('counting'); // counting | submitted | approved | rejected
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
        });

        Schema::create('inventory_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('count_id')->constrained('inventory_counts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->unsignedBigInteger('batch_id')->default(0);
            $table->decimal('system_quantity', 18, 4);
            $table->decimal('counted_quantity', 18, 4)->nullable();
            $table->decimal('variance', 18, 4)->nullable();
            $table->string('reason', 200)->nullable();
            $table->timestamps();
        });

        // Any change not backed by a document (damage, found stock, write-off, count variance) waits here for a second person.
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->unsignedBigInteger('batch_id')->default(0);
            $table->decimal('quantity', 18, 4); // signed
            $table->string('reason_code', 20); // damage | expired | theft | count_variance | found | other
            $table->string('reason', 300);
            $table->string('status', 10)->default('requested'); // requested | approved | rejected
            $table->unsignedBigInteger('count_id')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('inventory_recalls', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('batch_no', 60)->nullable(); // empty = every batch of the item
            $table->string('reason', 300);
            $table->decimal('quantity_blocked', 18, 4)->default(0);
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['inventory_recalls', 'inventory_adjustments', 'inventory_count_lines', 'inventory_counts', 'inventory_consumptions', 'inventory_issue_lines', 'inventory_issues', 'inventory_request_lines', 'inventory_requests', 'inventory_transfer_lines', 'inventory_transfers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
