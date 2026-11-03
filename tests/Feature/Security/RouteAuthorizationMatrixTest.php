<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Authorization matrix (guideline 6.7 T-S5 / pack P2) for every route that needs no record id:
 *  - signed out: never a 200 on a protected route;
 *  - signed in as an organization member who holds NO permission: never a 200 on a screen that is not open to everybody.
 * Routes with {parameters} are covered by the per-module tenant-isolation tests.
 */
class RouteAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** Screens every signed-in user may open (no data of their own organization beyond what they see anyway). */
    private const OPEN_TO_SIGNED_IN = [
        'admin/notifications', 'admin/messages', 'admin/help/quick-start', 'dashboard', 'change-password', 'admin/search',
        'api/v1/auth/me', 'api/v1/organizations', /* lists only the caller's own organizations */ 'api/v1/tenant/context', 'mfa/setup', 'mfa/challenge', 'email/verify', 'login/context', 'admin/profile', 'api/v1/portal/config', 'api/v1/fhir/metadata',
    ];

    /** @return list<array{string, string}> [method, uri] */
    private function parameterlessRoutes(string $method, callable $filter): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array($method, $r->methods(), true) && ! str_contains($r->uri(), '{') && $filter($r))
            ->map(fn ($r) => [$method, '/'.ltrim($r->uri(), '/')])->unique()->values()->all();
    }

    public function test_signed_out_visitors_never_get_a_page_from_a_protected_route(): void
    {
        $public = ['/', 'login', 'forgot-password', 'up', 'sanctum/csrf-cookie', 'api/v1/auth/login', 'api/v1/portal/config' /* app settings, no patient data */, 'api/v1/fhir/metadata' /* the capability statement, by FHIR convention */, 'portal/login', 'portal/register', 'portal/activate', 'portal/forgot' /* the patient sign-up and sign-in pages */];
        $checked = 0;

        foreach ($this->parameterlessRoutes('GET', fn ($r) => ! str_starts_with($r->uri(), '_') && ! str_starts_with($r->uri(), 'adminlte')) as [, $uri]) {
            if (in_array(ltrim($uri, '/'), $public, true) || str_starts_with(ltrim($uri, '/'), 'storage')) {
                continue;
            }
            $status = $this->get($uri)->getStatusCode();
            $this->assertNotSame(200, $status, "GET $uri answered 200 to a signed-out visitor");
            $checked++;
        }

        $this->assertGreaterThan(100, $checked, 'the matrix should cover the whole application');
    }

    public function test_a_member_without_permissions_cannot_open_permission_gated_screens(): void
    {
        $organization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'staff']);
        $user->branches()->attach($branch->id, ['access_level' => 'staff', 'organization_id' => $organization->id]);
        $this->actingAs($user);
        session()->put(['tenant_organization_id' => $organization->id, 'tenant_branch_id' => $branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);

        $leaks = [];
        $checked = 0;
        $routes = $this->parameterlessRoutes('GET', fn ($r) => str_starts_with($r->uri(), 'admin/') || str_starts_with($r->uri(), 'api/v1/'));

        foreach ($routes as [, $uri]) {
            if (in_array(ltrim($uri, '/'), self::OPEN_TO_SIGNED_IN, true)) {
                continue;
            }
            $response = $this->get($uri, ['Accept' => 'application/json']);
            $checked++;
            if ($response->getStatusCode() === 200) {
                $leaks[] = $uri;
            }
        }

        $this->assertGreaterThan(80, $checked);
        $this->assertSame([], $leaks, 'A member with no permission received a 200 from: '.implode(', ', $leaks));
    }
}
