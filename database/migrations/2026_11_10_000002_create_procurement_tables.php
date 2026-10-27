<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 15, part 2: requisition → RFQ/quotation → purchase order → goods receipt → supplier invoice (three-way match) → returns, budgets (S04–S07, S10, S11). */
return new class extends Migration
{
    public function up(): void
    {
        // One approval chain for every document that needs one: a row per level, decided by a different person each time.
        Schema::create('procurement_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 20); // requisition | purchase_order
            $table->unsignedBigInteger('document_id');
            $table->unsignedTinyInteger('level');
            $table->string('permission', 60);
            $table->string('status', 10)->default('pending'); // pending | approved | rejected
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('comment', 300)->nullable();
            $table->timestamps();

            $table->unique(['document_type', 'document_id', 'level'], 'uq_proc_approval_level');
        });

        Schema::create('procurement_requisitions', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->restrictOnDelete(); // where the goods are wanted
            $table->string('number', 40);
            $table->string('status', 20)->default('draft'); // draft | submitted | approved | rejected | ordered | cancelled
            $table->string('priority', 10)->default('routine'); // routine | urgent
            $table->date('needed_by')->nullable();
            $table->decimal('estimated_total', 14, 2)->default(0);
            $table->string('notes', 300)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('procurement_requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('procurement_requisitions')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity', 14, 4); // in purchase units
            $table->decimal('estimated_unit_price', 14, 4)->default(0);
            $table->decimal('ordered_quantity', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('procurement_rfqs', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requisition_id')->nullable()->constrained('procurement_requisitions')->nullOnDelete();
            $table->string('number', 40);
            $table->string('status', 12)->default('draft'); // draft | sent | awarded | cancelled
            $table->date('closes_on')->nullable();
            $table->unsignedBigInteger('awarded_supplier_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
        });

        Schema::create('procurement_rfq_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('procurement_rfqs')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->timestamps();
        });

        Schema::create('procurement_rfq_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('procurement_rfqs')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->unique(['rfq_id', 'supplier_id']);
        });

        Schema::create('procurement_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rfq_id')->constrained('procurement_rfqs')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->decimal('total', 14, 2)->default(0);
            $table->unsignedSmallInteger('delivery_days')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 10)->default('received'); // received | selected | rejected
            $table->string('notes', 300)->nullable();
            $table->timestamps();

            $table->unique(['rfq_id', 'supplier_id']);
        });

        Schema::create('procurement_quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('procurement_quotations')->cascadeOnDelete();
            $table->foreignId('rfq_line_id')->constrained('procurement_rfq_lines')->cascadeOnDelete();
            $table->decimal('unit_price', 14, 4);
            $table->timestamps();
        });

        Schema::create('procurement_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->restrictOnDelete(); // delivery store
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete(); // whose budget pays
            $table->unsignedBigInteger('requisition_id')->nullable();
            $table->unsignedBigInteger('rfq_id')->nullable();
            $table->string('number', 40);
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('po_type', 12)->default('standard'); // standard | emergency | consignment
            $table->string('status', 20)->default('draft'); // draft | pending_approval | approved | issued | partially_received | received | closed | cancelled
            $table->date('order_date');
            $table->date('expected_on')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('terms', 300)->nullable();
            $table->string('emergency_reason', 300)->nullable();
            $table->timestamp('ratify_by')->nullable(); // an emergency PO must be approved after the fact by this time
            $table->timestamp('ratified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->string('cancel_reason', 300)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status']);
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('procurement_po_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('procurement_purchase_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity', 14, 4); // purchase units
            $table->decimal('unit_price', 14, 4);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->decimal('received_quantity', 14, 4)->default(0); // accepted
            $table->decimal('rejected_quantity', 14, 4)->default(0);
            $table->boolean('closed')->default(false);
            $table->timestamps();
        });

        Schema::create('procurement_po_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('procurement_purchase_orders')->cascadeOnDelete();
            $table->unsignedSmallInteger('version'); // the version this snapshot preserves
            $table->json('snapshot');
            $table->string('reason', 300);
            $table->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('procurement_goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('procurement_purchase_orders')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->restrictOnDelete();
            $table->string('number', 40);
            $table->string('delivery_note', 80)->nullable();
            $table->date('received_on');
            $table->string('status', 20)->default('pending_inspection'); // pending_inspection | posted | cancelled
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
        });

        Schema::create('procurement_grn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('procurement_goods_receipts')->cascadeOnDelete();
            $table->foreignId('po_line_id')->constrained('procurement_po_lines')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('received_quantity', 14, 4);  // purchase units delivered
            $table->decimal('accepted_quantity', 14, 4)->default(0);
            $table->decimal('rejected_quantity', 14, 4)->default(0);
            $table->string('reject_reason', 200)->nullable();
            $table->string('batch_no', 60)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 14, 4)->default(0); // per base unit, from the PO price
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->restrictOnDelete();
            $table->foreignId('purchase_order_id')->constrained('procurement_purchase_orders')->restrictOnDelete();
            $table->string('invoice_number', 60);
            $table->date('invoice_date');
            $table->date('due_on')->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('status', 14)->default('recorded'); // recorded | matched | mismatch | approved | rejected
            $table->decimal('variance_amount', 14, 2)->default(0);
            $table->json('match_result')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approval_note', 300)->nullable();
            $table->string('finance_reference', 80)->nullable(); // set when the payable has been handed to Finance
            $table->timestamps();

            $table->unique(['supplier_id', 'invoice_number']);
        });

        Schema::create('procurement_supplier_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('procurement_supplier_invoices')->cascadeOnDelete();
            $table->foreignId('po_line_id')->constrained('procurement_po_lines')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 4);
            $table->timestamps();
        });

        Schema::create('procurement_supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('inventory_stores')->restrictOnDelete();
            $table->unsignedBigInteger('goods_receipt_id')->nullable();
            $table->string('number', 40);
            $table->string('reason', 300);
            $table->string('status', 12)->default('draft'); // draft | dispatched | credited
            $table->string('credit_note_number', 60)->nullable();
            $table->decimal('credit_amount', 14, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
        });

        Schema::create('procurement_supplier_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_return_id')->constrained('procurement_supplier_returns')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->unsignedBigInteger('batch_id')->default(0);
            $table->decimal('quantity', 14, 4); // base units
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('procurement_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->unique(['organization_id', 'department_id', 'period_start'], 'uq_proc_budget_period');
        });
    }

    public function down(): void
    {
        foreach (['procurement_budgets', 'procurement_supplier_return_lines', 'procurement_supplier_returns', 'procurement_supplier_invoice_lines', 'procurement_supplier_invoices', 'procurement_grn_lines', 'procurement_goods_receipts', 'procurement_po_amendments', 'procurement_po_lines', 'procurement_purchase_orders', 'procurement_quotation_lines', 'procurement_quotations', 'procurement_rfq_suppliers', 'procurement_rfq_lines', 'procurement_rfqs', 'procurement_requisition_lines', 'procurement_requisitions', 'procurement_approvals'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
