<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Department;
use Illuminate\Http\Request;

class TenantContextResolver
{
    private ?Organization $organization = null;

    private ?Branch $branch = null;

    private ?Department $department = null;

    private ?int $organizationId = null;

    private ?int $branchId = null;

    private ?int $departmentId = null;

    public function resolveFromRequest(Request $request): void
    {
        if ($request->hasHeader('X-Organization-Id')) {
            $this->setOrganizationId((int) $request->header('X-Organization-Id'));
        }

        if ($request->hasHeader('X-Branch-Id')) {
            $this->setBranchId((int) $request->header('X-Branch-Id'));
        }

        if ($request->hasHeader('X-Department-Id')) {
            $this->setDepartmentId((int) $request->header('X-Department-Id'));
        }

        if ($request->hasSession() && $request->session()->has('tenant_organization_id')) {
            $this->setOrganizationId($request->session()->get('tenant_organization_id'));
        }

        if ($request->hasSession() && $request->session()->has('tenant_branch_id')) {
            $this->setBranchId($request->session()->get('tenant_branch_id'));
        }

        if ($request->hasSession() && $request->session()->has('tenant_department_id')) {
            $this->setDepartmentId($request->session()->get('tenant_department_id'));
        }
    }

    public function setOrganizationId(int $organizationId): void
    {
        $this->organizationId = $organizationId;
        $this->organization = null;
        $this->branch = null;
        $this->branchId = null;
        $this->department = null;
        $this->departmentId = null;
    }

    public function setBranchId(int $branchId): void
    {
        $this->branchId = $branchId;
        $this->branch = null;
        $this->department = null;
        $this->departmentId = null;

        if ($this->branch) {
            $this->organizationId = $this->branch->organization_id;
            $this->organization = null;
        }
    }

    public function setDepartmentId(int $departmentId): void
    {
        $this->departmentId = $departmentId;
        $this->department = null;

        if ($this->department) {
            $this->branchId = $this->department->branch_id;
            $this->branch = null;
            $this->organizationId = $this->department->organization_id;
            $this->organization = null;
        }
    }

    public function getOrganizationId(): ?int
    {
        return $this->organizationId;
    }

    public function getBranchId(): ?int
    {
        return $this->branchId;
    }

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function getOrganization(): ?Organization
    {
        if ($this->organization === null && $this->organizationId !== null) {
            $this->organization = Organization::find($this->organizationId);
        }

        return $this->organization;
    }

    public function getBranch(): ?Branch
    {
        if ($this->branch === null && $this->branchId !== null) {
            $this->branch = Branch::find($this->branchId);
        }

        return $this->branch;
    }

    public function getDepartment(): ?Department
    {
        if ($this->department === null && $this->departmentId !== null) {
            $this->department = Department::find($this->departmentId);
        }

        return $this->department;
    }

    public function clear(): void
    {
        $this->organization = null;
        $this->branch = null;
        $this->department = null;
        $this->organizationId = null;
        $this->branchId = null;
        $this->departmentId = null;
    }
}
