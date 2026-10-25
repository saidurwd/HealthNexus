<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** H-12 step 1: nullable → back-fill → unique, for the three root records of the patient journey. */
return new class extends Migration
{
    private const TABLES = ['patients', 'encounters', 'appointments'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->char('public_id', 26)->nullable()->after('id'));

            DB::table($table)->whereNull('public_id')->orderBy('id')->each(
                fn ($row) => DB::table($table)->where('id', $row->id)->update(['public_id' => (string) Str::ulid()])
            );

            Schema::table($table, fn (Blueprint $t) => $t->unique('public_id', "uq_{$table}_public_id"));
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique("uq_{$table}_public_id");
                $t->dropColumn('public_id');
            });
        }
    }
};
