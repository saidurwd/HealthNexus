<?php

namespace Tests\Feature\Insurance;

use App\Models\Insurance\InsurancePlan;
use App\Services\Insurance\InsuranceCoverageEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Golden-file tests of the payer / patient split (guideline 26.3 S03). The cases live in tests/Fixtures/insurance/split_cases.json. */
class InsuranceCoverageEngineTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function cases(): array
    {
        $cases = json_decode(file_get_contents(__DIR__.'/../../Fixtures/insurance/split_cases.json'), true, flags: JSON_THROW_ON_ERROR);

        $out = [];
        foreach ($cases as $name => $case) {
            $out[$name] = [$name, $case];
        }

        return $out;
    }

    #[DataProvider('cases')]
    public function test_the_split_matches_the_golden_file(string $name, array $case): void
    {
        $plan = InsurancePlan::factory()->create($case['plan']);
        foreach ($case['rules'] as $rule) {
            $plan->rules()->create($rule + ['organization_id' => $plan->organization_id]);
        }

        $result = app(InsuranceCoverageEngine::class)->split($plan->refresh(), $case['lines'], $case['state'] ?? []);

        foreach ($case['expect'] as $key => $expected) {
            foreach ($expected as $field => $value) {
                $this->assertSame($value, $result['lines'][$key][$field], "$name: line $key $field");
            }
        }
        foreach ($case['totals'] as $field => $value) {
            $this->assertSame($value, $result['totals'][$field], "$name: total $field");
        }
        // the invariant, for every case: payer + patient + contractual adjustment = gross, to the paisa
        $this->assertSame($result['totals']['gross'], bcadd(bcadd($result['totals']['payer'], $result['totals']['patient'], 2), $result['totals']['adjustment'], 2), "$name: shares add up");
        foreach ($result['lines'] as $key => $line) {
            $this->assertSame($line['gross'], bcadd(bcadd($line['payer'], $line['patient'], 2), $line['adjustment'], 2), "$name: line $key adds up");
        }
    }
}
