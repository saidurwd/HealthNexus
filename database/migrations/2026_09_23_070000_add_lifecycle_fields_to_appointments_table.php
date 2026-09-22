<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('is_walk_in')->default(false)->after('reason');
            $table->boolean('is_follow_up')->default(false)->after('is_walk_in');
            $table->boolean('is_telemedicine')->default(false)->after('is_follow_up');
            $table->unsignedBigInteger('previous_appointment_id')->nullable()->after('is_telemedicine');
            $table->foreign('previous_appointment_id')->references('id')->on('appointments')->nullOnDelete();
            $table->string('referral_source')->nullable()->after('reason');
            $table->string('referred_by')->nullable()->after('referral_source');
            $table->timestamp('booked_at')->nullable()->after('created_at');
            $table->timestamp('confirmed_at')->nullable()->after('booked_at');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete()->after('confirmed_at');
            $table->timestamp('checked_in_at')->nullable()->after('confirmed_at');
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete()->after('checked_in_at');
            $table->timestamp('completed_at')->nullable()->after('checked_in_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->after('cancelled_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            $table->timestamp('no_show_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['previous_appointment_id']);
            $table->dropForeign(['confirmed_by']);
            $table->dropForeign(['checked_in_by']);
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn([
                'is_walk_in',
                'is_follow_up',
                'is_telemedicine',
                'previous_appointment_id',
                'referral_source',
                'referred_by',
                'booked_at',
                'confirmed_at',
                'confirmed_by',
                'checked_in_at',
                'checked_in_by',
                'completed_at',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
                'no_show_at',
            ]);
        });
    }
};
