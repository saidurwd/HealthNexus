<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantContextResolverTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new TenantContextResolver;
    }

    public function test_can_set_and_get_company_id(): void
    {
        $this->resolver->setCompanyId(1);

        $this->assertEquals(1, $this->resolver->getCompanyId());
    }

    public function test_setting_branch_clears_department(): void
    {
        $this->resolver->setCompanyId(1);
        $this->resolver->setDepartmentId(5);

        $this->resolver->setBranchId(2);

        $this->assertEquals(2, $this->resolver->getBranchId());
        $this->assertNull($this->resolver->getDepartmentId());
    }

    public function test_clear_resolves_all_contexts(): void
    {
        $this->resolver->setCompanyId(1);
        $this->resolver->setBranchId(2);
        $this->resolver->setDepartmentId(3);

        $this->resolver->clear();

        $this->assertNull($this->resolver->getCompanyId());
        $this->assertNull($this->resolver->getBranchId());
        $this->assertNull($this->resolver->getDepartmentId());
    }

    public function test_can_resolve_company_model(): void
    {
        $company = Company::factory()->create();

        $this->resolver->setCompanyId($company->id);

        $this->assertTrue($this->resolver->getCompany()->is($company));
    }

    public function test_can_resolve_branch_model(): void
    {
        $branch = Branch::factory()->create();

        $this->resolver->setBranchId($branch->id);

        $this->assertTrue($this->resolver->getBranch()->is($branch));
    }

    public function test_can_resolve_department_model(): void
    {
        $department = Department::factory()->create();

        $this->resolver->setDepartmentId($department->id);

        $this->assertTrue($this->resolver->getDepartment()->is($department));
    }
}
