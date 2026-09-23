<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Creates a Provider row for every distinct (company_id, doctor_id) pair already referenced
     * by doctor_schedules/appointments, and backfills the new provider_id column on both — so
     * existing data keeps working under the new generic Provider abstraction without requiring
     * every historical doctor_id consumer to be rewritten in the same release.
     */
    public function up(): void
    {
        $pairs = DB::table('doctor_schedules')
            ->select('company_id', 'branch_id', 'doctor_id')
            ->union(DB::table('appointments')->select('company_id', 'branch_id', 'doctor_id'))
            ->get()
            ->unique(fn ($row) => $row->company_id.'-'.$row->doctor_id);

        foreach ($pairs as $pair) {
            $providerId = DB::table('providers')
                ->where('company_id', $pair->company_id)
                ->where('user_id', $pair->doctor_id)
                ->value('id');

            if (! $providerId) {
                $user = DB::table('users')->where('id', $pair->doctor_id)->first();

                if (! $user) {
                    continue;
                }

                $providerId = DB::table('providers')->insertGetId([
                    'company_id' => $pair->company_id,
                    'branch_id' => $pair->branch_id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'provider_type' => 'doctor',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('doctor_schedules')
                ->where('company_id', $pair->company_id)
                ->where('doctor_id', $pair->doctor_id)
                ->update(['provider_id' => $providerId]);

            DB::table('appointments')
                ->where('company_id', $pair->company_id)
                ->where('doctor_id', $pair->doctor_id)
                ->update(['provider_id' => $providerId]);
        }
    }

    public function down(): void
    {
        // Data backfill only — the provider_id columns themselves are dropped by their own
        // schema migrations' down() methods.
    }
};
