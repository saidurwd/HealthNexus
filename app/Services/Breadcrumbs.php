<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

class Breadcrumbs
{
    public function __construct(protected Request $request) {}

    public function generate(?Route $route = null): array
    {
        $route = $route ?? $this->request->route();

        if (! $route) {
            return [];
        }

        $name = $route->getName();
        $params = $route->parameters();

        return $this->build($name, $params);
    }

    protected function build(string $name, array $params): array
    {
        $crumbs = [];

        if (str_starts_with($name, 'admin.')) {
            $crumbs[] = ['text' => 'Dashboard', 'url' => route('home')];

            if (str_contains($name, 'companies') && ! str_starts_with($name, 'admin.companies')) {
                if (isset($params['company'])) {
                    $crumbs[] = ['text' => 'Companies', 'url' => route('admin.companies.index')];
                }
            }
        } elseif ($name === 'home') {
            return [['text' => 'Dashboard', 'url' => null]];
        } else {
            $crumbs[] = ['text' => 'Home', 'url' => route('home')];
        }

        $map = $this->routeMap();

        foreach ($map as $pattern => $label) {
            if ($this->matches($pattern, $name)) {
                $crumb = ['text' => $label];

                if ($this->isLinkable($pattern, $name, $params)) {
                    $crumb['url'] = $this->buildUrl($pattern, $name, $params);
                }

                $crumbs[] = $crumb;
            }
        }

        if (isset($params['user']) && str_contains($name, 'users')) {
            $user = $params['user'];
            if (in_array($name, ['admin.users.show', 'admin.users.edit', 'admin.users.update', 'admin.users.company-show', 'admin.users.company-edit', 'admin.users.company-update'])) {
                $crumbs[] = ['text' => $user instanceof \App\Models\User ? $user->name : 'User'];
            }
        }

        if (isset($params['patient']) && str_contains($name, 'patients')) {
            $patient = $params['patient'];
            $crumbs[] = ['text' => $patient instanceof \App\Models\Patient ? $patient->name : 'Patient'];
        }

        if (isset($params['encounter']) && str_contains($name, 'encounters')) {
            $encounter = $params['encounter'];
            $crumbs[] = ['text' => 'Encounter'];
        }

        if (isset($params['company']) && str_contains($name, 'companies')) {
            $company = $params['company'];
            $crumbs[] = ['text' => $company instanceof \App\Models\Company ? $company->name : 'Company'];
        }

        if (isset($params['department']) && str_contains($name, 'departments')) {
            $department = $params['department'];
            $crumbs[] = ['text' => $department instanceof \App\Models\Department ? $department->name : 'Department'];
        }

        if (isset($params['role']) && str_contains($name, 'roles')) {
            $crumbs[] = ['text' => 'Role'];
        }

        if (isset($params['permission']) && str_contains($name, 'permissions')) {
            $crumbs[] = ['text' => 'Permission'];
        }

        if (isset($params['country']) && str_contains($name, 'countries')) {
            $crumbs[] = ['text' => 'Country'];
        }

        if (isset($params['state']) && str_contains($name, 'states')) {
            $crumbs[] = ['text' => 'State'];
        }

        if (isset($params['currency']) && str_contains($name, 'currencies')) {
            $crumbs[] = ['text' => 'Currency'];
        }

        if (isset($params['identificationType']) && str_contains($name, 'identification-types')) {
            $crumbs[] = ['text' => 'Identification Type'];
        }

        return $crumbs;
    }

    protected function routeMap(): array
    {
        return [
            'admin.companies' => 'Companies',
            'admin.companies.create' => 'New Company',
            'admin.branches.index' => 'Branches',
            'admin.branches.create' => 'New Branch',
            'admin.branches.show' => 'Branch Details',
            'admin.branches.edit' => 'Edit Branch',
            'admin.departments' => 'Departments',
            'admin.departments.create' => 'New Department',
            'admin.departments.global' => 'Departments',
            'admin.departments.create.global' => 'New Department',
            'admin.users' => 'Users',
            'admin.users.create' => 'New User',
            'admin.users.show' => 'User Details',
            'admin.users.edit' => 'Edit User',
            'admin.patients' => 'Patients',
            'admin.patients.create' => 'New Patient',
            'admin.patients.show' => 'Patient Details',
            'admin.patients.edit' => 'Edit Patient',
            'admin.encounters' => 'Encounters',
            'admin.encounters.create' => 'New Encounter',
            'admin.encounters.show' => 'Encounter Details',
            'admin.encounters.edit' => 'Edit Encounter',
            'admin.roles' => 'Roles',
            'admin.roles.create' => 'New Role',
            'admin.roles.edit' => 'Edit Role',
            'admin.permissions' => 'Permissions',
            'admin.permissions.create' => 'New Permission',
            'admin.permissions.edit' => 'Edit Permission',
            'admin.master-data.index' => 'Master Data',
            'admin.master-data.countries' => 'Countries',
            'admin.master-data.countries.create' => 'New Country',
            'admin.master-data.countries.edit' => 'Edit Country',
            'admin.master-data.all-states' => 'States',
            'admin.master-data.states.create' => 'New State',
            'admin.master-data.states.edit' => 'Edit State',
            'admin.master-data.currencies' => 'Currencies',
            'admin.master-data.currencies.create' => 'New Currency',
            'admin.master-data.currencies.edit' => 'Edit Currency',
            'admin.master-data.identification-types' => 'Identification Types',
            'admin.master-data.identification-types.create' => 'New Identification Type',
            'admin.master-data.identification-types.edit' => 'Edit Identification Type',
            'admin.audit.index' => 'Audit Logs',
            'admin.audit.show' => 'Audit Log Details',
        ];
    }

    protected function matches(string $pattern, string $name): bool
    {
        if ($pattern === $name) {
            return true;
        }

        $patternSegments = explode('.', $pattern);
        $nameSegments = explode('.', $name);

        if (count($nameSegments) < count($patternSegments)) {
            return false;
        }

        foreach ($patternSegments as $i => $segment) {
            if (! isset($nameSegments[$i]) || $nameSegments[$i] !== $segment) {
                return false;
            }
        }

        return true;
    }

    protected function isLinkable(string $pattern, string $name, array $params): bool
    {
        if ($name === $pattern) {
            return true;
        }

        return false;
    }

    protected function buildUrl(string $pattern, string $name, array $params): ?string
    {
        if ($name === $pattern) {
            return match ($name) {
                'admin.companies.index' => route('admin.companies.index'),
                'admin.companies.create' => route('admin.companies.create'),
                'admin.users.index' => route('admin.users.index'),
                'admin.users.create' => route('admin.users.create'),
                'admin.users.show' => route('admin.users.show', $params['user']),
                'admin.users.edit' => route('admin.users.edit', $params['user']),
                'admin.patients.index' => route('admin.patients.index'),
                'admin.patients.create' => route('admin.patients.create'),
                'admin.patients.show' => route('admin.patients.show', $params['patient']),
                'admin.patients.edit' => route('admin.patients.edit', $params['patient']),
                'admin.encounters.index' => route('admin.encounters.index'),
                'admin.encounters.create' => route('admin.encounters.create'),
                'admin.encounters.show' => route('admin.encounters.show', $params['encounter']),
                'admin.encounters.edit' => route('admin.encounters.edit', $params['encounter']),
                'admin.roles.index' => route('admin.roles.index'),
                'admin.roles.create' => route('admin.roles.create'),
                'admin.roles.edit' => route('admin.roles.edit', $params['role']),
                'admin.permissions.index' => route('admin.permissions.index'),
                'admin.permissions.create' => route('admin.permissions.create'),
                'admin.permissions.edit' => route('admin.permissions.edit', $params['permission']),
                'admin.master-data.index' => route('admin.master-data.index'),
                'admin.master-data.countries' => route('admin.master-data.countries'),
                'admin.master-data.countries.create' => route('admin.master-data.countries.create'),
                'admin.master-data.countries.edit' => route('admin.master-data.countries.edit', $params['country']),
                'admin.master-data.all-states' => route('admin.master-data.all-states'),
                'admin.master-data.states.create' => route('admin.master-data.states.create'),
                'admin.master-data.states.edit' => route('admin.master-data.states.edit', $params['state']),
                'admin.master-data.currencies' => route('admin.master-data.currencies'),
                'admin.master-data.currencies.create' => route('admin.master-data.currencies.create'),
                'admin.master-data.currencies.edit' => route('admin.master-data.currencies.edit', $params['currency']),
                'admin.master-data.identification-types' => route('admin.master-data.identification-types'),
                'admin.master-data.identification-types.create' => route('admin.master-data.identification-types.create'),
                'admin.master-data.identification-types.edit' => route('admin.master-data.identification-types.edit', $params['identificationType']),
                'admin.audit.index' => route('admin.audit.index'),
                'admin.audit.show' => route('admin.audit.show', $params['auditLog']),
                default => null,
            };
        }

        return null;
    }

    public function render(): string
    {
        $crumbs = $this->generate();

        if (empty($crumbs)) {
            return '';
        }

        $html = '';

        foreach ($crumbs as $i => $crumb) {
            $isLast = $i === count($crumbs) - 1;

            if ($isLast) {
                $html .= '<li class="breadcrumb-item active">' . e($crumb['text']) . '</li>';
            } else {
                $url = $crumb['url'] ?? '#';
                $html .= '<li class="breadcrumb-item"><a href="' . e($url) . '">' . e($crumb['text']) . '</a></li>';
            }
        }

        return $html;
    }
}
