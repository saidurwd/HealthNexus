<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usage IS the record (decision #13) — no OT-side inventory tracked here, per the explicit
     * non-goal. Each row is its own billing idempotency anchor (decision #9), mirroring
     * IpdBedChargeEvent's per-day-anchor pattern applied per-implant instead.
     */
    public function up(): void
    {
        Schema::create('ot_implants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
            $table->string('implant_name');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('lot_batch')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('implanted_at');
            $table->foreignId('implanted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('billing_charge_id')->nullable();
            $table->timestamps();

            $table->index('surgery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_implants');
    }
};
