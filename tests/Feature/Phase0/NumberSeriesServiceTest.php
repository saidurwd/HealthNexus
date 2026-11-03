<?php

namespace Tests\Feature\Phase0;

use App\Models\Branch;
use App\Models\Organization;
use App\Services\Numbering\NumberSeriesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NumberSeriesServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fifty_calls_give_fifty_unique_ordered_numbers(): void
    {
        $organization = Organization::factory()->create();
        $service = app(NumberSeriesService::class);

        $numbers = [];
        for ($i = 0; $i < 50; $i++) {
            $numbers[] = $service->next('t.doc', $organization->id, prefix: 'T', format: '{PREFIX}-{SEQ:4}');
        }

        $this->assertCount(50, array_unique($numbers));
        $this->assertSame('T-0001', $numbers[0]);
        $this->assertSame('T-0050', $numbers[49]);
        $this->assertSame(50, DB::table('number_allocations')->count());
    }

    public function test_series_are_isolated_by_organization_and_branch(): void
    {
        [$a, $b] = Organization::factory()->count(2)->create();
        $branch = Branch::factory()->create(['organization_id' => $a->id]);
        $service = app(NumberSeriesService::class);

        $this->assertSame('1', $service->next('x', $a->id, format: '{SEQ:1}'));
        $this->assertSame('2', $service->next('x', $a->id, format: '{SEQ:1}'));
        $this->assertSame('1', $service->next('x', $b->id, format: '{SEQ:1}'));
        $this->assertSame('1', $service->next('x', $a->id, $branch->id, format: '{SEQ:1}'));
    }

    public function test_yearly_period_restarts_the_sequence(): void
    {
        $organization = Organization::factory()->create();
        $service = app(NumberSeriesService::class);

        Carbon::setTestNow('2026-12-31 10:00:00');
        $this->assertSame('ENC-2026-00000001', $service->next('enc', $organization->id, prefix: 'ENC', period: 'year'));
        $this->assertSame('ENC-2026-00000002', $service->next('enc', $organization->id, prefix: 'ENC', period: 'year'));

        Carbon::setTestNow('2027-01-01 00:05:00');
        $this->assertSame('ENC-2027-00000001', $service->next('enc', $organization->id, prefix: 'ENC', period: 'year'));

        Carbon::setTestNow();
    }

    public function test_a_rolled_back_caller_does_not_consume_a_number(): void
    {
        $organization = Organization::factory()->create();
        $service = app(NumberSeriesService::class);

        try {
            DB::transaction(function () use ($service, $organization) {
                $service->next('r', $organization->id, format: '{SEQ:1}');
                throw new \RuntimeException('document failed');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame('1', $service->next('r', $organization->id, format: '{SEQ:1}'));
    }
}
