<?php

namespace Tests\Feature\Patients;

use App\Models\Branch;
use App\Models\Company;
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

    public function test_enterprise_numbers_increment_sequentially_per_company(): void
    {
        $company = Company::factory()->create(['code' => 'ABC']);
        $generator = app(PatientNumberGenerator::class);

        $first = $generator->generateEnterprisePatientNo($company);
        $second = $generator->generateEnterprisePatientNo($company);
        $third = $generator->generateEnterprisePatientNo($company);

        $this->assertSame('ABC-00000001', $first);
        $this->assertSame('ABC-00000002', $second);
        $this->assertSame('ABC-00000003', $third);
    }

    public function test_local_numbers_increment_sequentially_per_branch(): void
    {
        $company = Company::factory()->create();
        $branchA = Branch::factory()->create(['company_id' => $company->id, 'code' => 'BRA']);
        $branchB = Branch::factory()->create(['company_id' => $company->id, 'code' => 'BRB']);
        $generator = app(PatientNumberGenerator::class);

        $a1 = $generator->generateLocalPatientNo($company, $branchA);
        $b1 = $generator->generateLocalPatientNo($company, $branchB);
        $a2 = $generator->generateLocalPatientNo($company, $branchA);

        $this->assertSame('BRA-00000001', $a1);
        $this->assertSame('BRB-00000001', $b1);
        $this->assertSame('BRA-00000002', $a2);
    }

    public function test_counters_are_isolated_per_company(): void
    {
        $companyA = Company::factory()->create(['code' => 'AAA']);
        $companyB = Company::factory()->create(['code' => 'BBB']);
        $generator = app(PatientNumberGenerator::class);

        $generator->generateEnterprisePatientNo($companyA);
        $generator->generateEnterprisePatientNo($companyA);
        $firstForB = $generator->generateEnterprisePatientNo($companyB);

        $this->assertSame('BBB-00000001', $firstForB);
        $this->assertSame(2, PatientNumberCounter::where('company_id', $companyA->id)->value('last_number'));
    }
}
