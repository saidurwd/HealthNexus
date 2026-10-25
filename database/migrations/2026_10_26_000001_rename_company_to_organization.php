<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Decision D-02: the tenant is an Organization, never a "company".
 *
 * Fresh installs already create organization-named tables (the original
 * migrations were updated in place). This migration upgrades databases that
 * were built before the rename and is a no-op everywhere else.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies') || Schema::hasTable('organizations')) {
            return;
        }

        $driver = DB::getDriverName();

        Schema::disableForeignKeyConstraints();

        Schema::rename('companies', 'organizations');

        if (Schema::hasTable('user_companies')) {
            Schema::rename('user_companies', 'user_organizations');
        }

        $this->tablesWithColumn('company_id', $driver)->each(function (string $table) {
            Schema::table($table, fn ($t) => $t->renameColumn('company_id', 'organization_id'));
        });

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('name', 'like', '%companies%')->get()->each(
                fn ($p) => DB::table('permissions')->where('id', $p->id)
                    ->update(['name' => str_replace('companies', 'organizations', $p->name)])
            );
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // One-way upgrade: the old "company" naming is retired.
    }

    private function tablesWithColumn(string $column, string $driver)
    {
        if ($driver === 'mysql' || $driver === 'mariadb') {
            return collect(DB::select(
                'select table_name as t from information_schema.columns where table_schema = database() and column_name = ?',
                [$column]
            ))->pluck('t');
        }

        return collect(Schema::getTables())->pluck('name')
            ->filter(fn ($name) => Schema::hasColumn($name, $column))->values();
    }
};
