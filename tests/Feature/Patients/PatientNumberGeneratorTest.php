<?php

namespace Tests\Feature\Patients;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\PatientNumberCounter;
use App\Services\Patients\PatientNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The previous generator derived the next number from "highest existing id/registration + 1",
 * which two concurrent requests could both read before either wrote — handing out the same
 * patient number twice. The row-locked counter table serializes concurrent callers instead.
 */
class PatientNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_enterprise_numbers_increment_sequentially_per_organization(): void
    {
        $organization = Organization::factory()->create(['code' => 'ABC']);
        $generator = app(PatientNumberGenerator::class);

        $first = $generator->generateEnterprisePatientNo($organization);
        $second = $generator->generateEnterprisePatientNo($organization);
        $third = $generator->generateEnterprisePatientNo($organization);

        $this->assertSame('ABC-00000001', $first);
        $this->assertSame('ABC-00000002', $second);
        $this->assertSame('ABC-00000003', $third);
    }

    public function test_local_numbers_increment_sequentially_per_branch(): void
    {
        $organization = Organization::factory()->create();
        $branchA = Branch::factory()->create(['organization_id' => $organization->id, 'code' => 'BRA']);
        $branchB = Branch::factory()->create(['organization_id' => $organization->id, 'code' => 'BRB']);
        $generator = app(PatientNumberGenerator::class);

        $a1 = $generator->generateLocalPatientNo($organization, $branchA);
        $b1 = $generator->generateLocalPatientNo($organization, $branchB);
        $a2 = $generator->generateLocalPatientNo($organization, $branchA);

        $this->assertSame('BRA-00000001', $a1);
        $this->assertSame('BRB-00000001', $b1);
        $this->assertSame('BRA-00000002', $a2);
    }

    public function test_counters_are_isolated_per_organization(): void
    {
        $organizationA = Organization::factory()->create(['code' => 'AAA']);
        $organizationB = Organization::factory()->create(['code' => 'BBB']);
        $generator = app(PatientNumberGenerator::class);

        $generator->generateEnterprisePatientNo($organizationA);
        $generator->generateEnterprisePatientNo($organizationA);
        $firstForB = $generator->generateEnterprisePatientNo($organizationB);

        $this->assertSame('BBB-00000001', $firstForB);
        $this->assertSame(2, PatientNumberCounter::where('organization_id', $organizationA->id)->value('last_number'));
    }
}
