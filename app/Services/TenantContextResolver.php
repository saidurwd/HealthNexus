<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;

class TenantContextResolver
{
    private ?Company $company = null;

    private ?Branch $branch = null;

    private ?Department $department = null;

    private ?int $companyId = null;

    private ?int $branchId = null;

    private ?int $departmentId = null;

    public function resolveFromRequest(Request $request): void
    {
        if ($request->hasHeader('X-Company-Id')) {
            $this->setCompanyId((int) $request->header('X-Company-Id'));
        }

        if ($request->hasHeader('X-Branch-Id')) {
            $this->setBranchId((int) $request->header('X-Branch-Id'));
        }

        if ($request->hasHeader('X-Department-Id')) {
            $this->setDepartmentId((int) $request->header('X-Department-Id'));
        }

        if ($request->hasSession() && $request->session()->has('tenant_company_id')) {
            $this->setCompanyId($request->session()->get('tenant_company_id'));
        }

        if ($request->hasSession() && $request->session()->has('tenant_branch_id')) {
            $this->setBranchId($request->session()->get('tenant_branch_id'));
        }

        if ($request->hasSession() && $request->session()->has('tenant_department_id')) {
            $this->setDepartmentId($request->session()->get('tenant_department_id'));
        }
    }

    public function setCompanyId(int $companyId): void
    {
        $this->companyId = $companyId;
        $this->company = null;
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
            $this->companyId = $this->branch->company_id;
            $this->company = null;
        }
    }

    public function setDepartmentId(int $departmentId): void
    {
        $this->departmentId = $departmentId;
        $this->department = null;

        if ($this->department) {
            $this->branchId = $this->department->branch_id;
            $this->branch = null;
            $this->companyId = $this->department->company_id;
            $this->company = null;
        }
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function getBranchId(): ?int
    {
        return $this->branchId;
    }

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function getCompany(): ?Company
    {
        if ($this->company === null && $this->companyId !== null) {
            $this->company = Company::find($this->companyId);
        }

        return $this->company;
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
        $this->company = null;
        $this->branch = null;
        $this->department = null;
        $this->companyId = null;
        $this->branchId = null;
        $this->departmentId = null;
    }
}
