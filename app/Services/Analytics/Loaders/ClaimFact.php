<?php

namespace App\Services\Analytics\Loaders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Insurance claims with how long each open one has waited. Rebuilt from all claims each run because the age of an open claim changes every day. */
class ClaimFact extends Loader
{
    private const CLOSED = ['draft', 'paid', 'closed', 'cancelled', 'written_off', 'denied'];

    public function key(): string
    {
        return 'fact_claims';
    }

    public function label(): string
    {
        return 'Insurance claims';
    }

    public function mode(): string
    {
        return 'snapshot';
    }

    protected function target(): string
    {
        return 'fact_claims';
    }

    protected function uniqueBy(): array
    {
        return ['claim_id'];
    }

    protected function source(): Builder
    {
        return DB::table('insurance_claims')->select('insurance_claims.id', 'insurance_claims.organization_id', 'insurance_claims.branch_id', 'insurance_claims.payer_id', 'insurance_claims.status', 'insurance_claims.service_date', 'insurance_claims.gross_amount',
            'insurance_claims.payer_share', 'insurance_claims.paid_amount', 'insurance_claims.adjusted_amount', 'insurance_claims.submitted_at', 'insurance_claims.created_at');
    }

    protected function idColumn(): string
    {
        return 'insurance_claims.id';
    }

    public function load(?string $watermark): array
    {
        $read = $written = 0;
        $today = CarbonImmutable::now((string) config('analytics.timezone', 'Asia/Dhaka'))->startOfDay();
        $this->source()->chunkById($this->chunk(), function (Collection $rows) use (&$read, &$written, $today) {
            $mapped = $rows->map(function ($r) use ($today) {
                $open = ! in_array($r->status, self::CLOSED, true);
                $outstanding = max(0, round((float) $r->payer_share - (float) $r->paid_amount - (float) $r->adjusted_amount, 2));
                $since = $this->localDay($r->submitted_at) ?? $this->day($r->service_date) ?? $this->localDay($r->created_at);

                return ['claim_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $this->day($r->service_date) ?? $since ?? '19700101'), 'payer_id' => $r->payer_id, 'status' => $r->status,
                    'gross' => $r->gross_amount, 'payer_share' => $r->payer_share, 'paid' => $r->paid_amount, 'adjusted' => $r->adjusted_amount, 'outstanding' => $open ? $outstanding : 0, 'age_days' => $open && $since ? max(0, (int) CarbonImmutable::parse($since)->diffInDays($today, false)) : 0,
                    'is_denied' => $r->status === 'denied', 'is_open' => $open];
            })->all();
            $written += $this->warehouse->upsert('fact_claims', $this->stamp($mapped), ['claim_id']);
            $read += $rows->count();
        }, $this->idColumn(), 'id');

        return ['read' => $read, 'written' => $written, 'watermark' => $today->toDateString()];
    }
}
