<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * H-12 follow-up: a ULID `public_id` for every patient-identifying, clinical-event and money-event record that appears in a URL
 * (guideline 9.1). Nullable → back-filled → unique, as in the first step. Raw inserts that skip the model get a public id the first
 * time the record is linked (see HasPublicId::getRouteKey).
 */
return new class extends Migration
{
    private const TABLES = [
        'billing_insurance_policies',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'public_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->char('public_id', 26)->nullable()->after('id'));
            }

            DB::table($table)->whereNull('public_id')->orderBy('id')->chunkById(1000, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update(['public_id' => (string) Str::ulid()]);
                }
            });

            Schema::table($table, fn (Blueprint $t) => $t->unique('public_id', "uq_{$table}_public_id"));
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique("uq_{$table}_public_id");
                $t->dropColumn('public_id');
            });
        }
    }
};
