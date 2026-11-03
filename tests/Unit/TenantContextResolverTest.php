<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Organization;
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

    public function test_can_set_and_get_organization_id(): void
    {
        $this->resolver->setOrganizationId(1);

        $this->assertEquals(1, $this->resolver->getOrganizationId());
    }

    public function test_setting_branch_clears_department(): void
    {
        $this->resolver->setOrganizationId(1);
        $this->resolver->setDepartmentId(5);

        $this->resolver->setBranchId(2);

        $this->assertEquals(2, $this->resolver->getBranchId());
        $this->assertNull($this->resolver->getDepartmentId());
    }

    public function test_clear_resolves_all_contexts(): void
    {
        $this->resolver->setOrganizationId(1);
        $this->resolver->setBranchId(2);
        $this->resolver->setDepartmentId(3);

        $this->resolver->clear();

        $this->assertNull($this->resolver->getOrganizationId());
        $this->assertNull($this->resolver->getBranchId());
        $this->assertNull($this->resolver->getDepartmentId());
    }

    public function test_can_resolve_organization_model(): void
    {
        $organization = Organization::factory()->create();

        $this->resolver->setOrganizationId($organization->id);

        $this->assertTrue($this->resolver->getOrganization()->is($organization));
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
