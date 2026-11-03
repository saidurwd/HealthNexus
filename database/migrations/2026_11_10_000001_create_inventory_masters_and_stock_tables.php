<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Phase 15, part 1: item master, stores, batches, stock projection, suppliers (guideline 26.4, S01–S03). Quantities are DECIMAL(18,4); money DECIMAL(14,2). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 120);
            $table->string('kind', 20)->default('consumable'); // consumable | surgical | linen | stationery | housekeeping | spare | reagent | equipment
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('inventory_categories')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 200);
            $table->string('base_uom', 20);                 // the unit stock is counted in
            $table->string('purchase_uom', 20)->nullable(); // the unit it is bought in (box, carton)
            $table->decimal('purchase_to_base', 14, 4)->default(1); // base units in one purchase unit
            $table->boolean('tracks_batch')->default(false);
            $table->boolean('tracks_expiry')->default(false);
            $table->boolean('tracks_serial')->default(false);
            $table->string('issue_rule', 8)->default('fefo'); // fefo | fifo
            $table->decimal('reorder_min', 14, 4)->default(0);
            $table->decimal('reorder_max', 14, 4)->default(0);
            $table->unsignedSmallInteger('lead_time_days')->default(7);
            $table->decimal('standard_cost', 14, 4)->default(0);
            $table->decimal('last_cost', 14, 4)->nullable();
            $table->string('charge_service_code', 40)->nullable(); // a patient-care item bills through Billing (never priced here)
            $table->unsignedBigInteger('medication_id')->nullable(); // bridge to the Phase 7 drug master (no copy of it)
            $table->string('status', 12)->default('active'); // active | inactive | discontinued
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'category_id', 'status'], 'idx_inv_items_category');
        });

        Schema::create('inventory_item_uoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('uom', 20);
            $table->decimal('factor_to_base', 14, 4); // base units in one of this unit
            $table->timestamps();

            $table->unique(['item_id', 'uom']);
        });

        Schema::create('inventory_stores', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 120);
            $table->string('kind', 14)->default('central'); // central | sub | ward | ot | housekeeping | quarantine
            $table->boolean('allow_negative')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('inventory_stores')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 120);
            $table->timestamps();

            $table->unique(['store_id', 'code']);
        });

        Schema::create('procurement_suppliers', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 200);
            $table->string('status', 14)->default('prospective'); // prospective | qualified | suspended | disqualified
            $table->string('contact_name', 120)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 300)->nullable();
            $table->string('bin_number', 40)->nullable(); // VAT / BIN
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->decimal('rating', 4, 2)->nullable(); // latest evaluation score, 0–5
            $table->string('status_reason', 300)->nullable();
            $table->foreignId('qualified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('procurement_supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->string('doc_type', 20); // trade_licence | drug_licence | vat_bin | tax_certificate | other
            $table->string('number', 80)->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->unsignedBigInteger('file_id')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'doc_type']);
            $table->index('expires_on');
        });

        Schema::create('procurement_supplier_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->date('period_end');
            $table->decimal('delivery_score', 4, 2);
            $table->decimal('quality_score', 4, 2);
            $table->decimal('price_score', 4, 2);
            $table->decimal('total_score', 4, 2);
            $table->string('notes', 300)->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'period_end']);
        });

        Schema::create('procurement_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('unit_price', 14, 4); // per purchase unit
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('reference', 80)->nullable();
            $table->timestamps();

            $table->index(['item_id', 'supplier_id', 'valid_from'], 'idx_proc_contract_lookup');
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('batch_no', 60);
            $table->date('expiry_date')->nullable();
            $table->date('received_on')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->decimal('unit_cost', 14, 4)->default(0); // per base unit
            $table->string('status', 12)->default('active'); // active | quarantined | recalled | expired
            $table->string('status_reason', 300)->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'batch_no', 'supplier_id'], 'uq_inv_batch');
            $table->index(['organization_id', 'expiry_date'], 'idx_inv_batch_expiry');
        });

        // Projection of the stock ledger (rebuildable from it). batch_id 0 = the item is not tracked by batch.
        Schema::create('inventory_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedBigInteger('batch_id')->default(0);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('quantity_on_hand', 18, 4)->default(0);
            $table->decimal('quantity_reserved', 18, 4)->default(0); // reduces available, not on-hand (12.2 rule 7)
            $table->timestamps();

            $table->unique(['store_id', 'item_id', 'batch_id'], 'uq_inv_stock_position');
            $table->index(['organization_id', 'item_id'], 'idx_inv_stock_item');
        });

        Schema::create('inventory_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('serial_no', 80);
            $table->string('status', 12)->default('in_stock'); // in_stock | issued | returned | scrapped
            $table->timestamps();

            $table->unique(['item_id', 'serial_no']);
        });

        $this->views();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_stock_valuation');
        DB::statement('DROP VIEW IF EXISTS v_expiry_risk');
        foreach (['inventory_serials', 'inventory_stock', 'inventory_batches', 'procurement_contracts', 'procurement_supplier_evaluations', 'procurement_supplier_documents', 'procurement_suppliers', 'inventory_locations', 'inventory_stores', 'inventory_item_uoms', 'inventory_items', 'inventory_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    /** Read-only views for management reports (guideline 12.4). Dropped first so a half-finished earlier migrate cannot block a fresh install. */
    private function views(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_stock_valuation');
        DB::statement('CREATE VIEW v_stock_valuation AS
            SELECT s.organization_id, s.store_id, s.item_id, s.batch_id, s.quantity_on_hand,
                   COALESCE(b.unit_cost, i.standard_cost) AS unit_cost,
                   s.quantity_on_hand * COALESCE(b.unit_cost, i.standard_cost) AS value
            FROM inventory_stock s
            JOIN inventory_items i ON i.id = s.item_id
            LEFT JOIN inventory_batches b ON b.id = s.batch_id');
        DB::statement('DROP VIEW IF EXISTS v_expiry_risk');
        DB::statement('CREATE VIEW v_expiry_risk AS
            SELECT s.organization_id, s.store_id, s.item_id, s.batch_id, b.batch_no, b.expiry_date, s.quantity_on_hand,
                   s.quantity_on_hand * b.unit_cost AS value_at_risk
            FROM inventory_stock s
            JOIN inventory_batches b ON b.id = s.batch_id
            WHERE s.quantity_on_hand > 0 AND b.expiry_date IS NOT NULL');
    }
};
