<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ReceiptService::void() had nowhere to persist who voided a receipt, why, or when — the
 * financial-audit rule (every state change captures user/reason/timestamp) was unenforceable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_receipts', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('issued_by');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('voided_reason')->nullable()->after('voided_by');
        });
    }

    public function down(): void
    {
        Schema::table('billing_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'voided_reason']);
        });
    }
};
