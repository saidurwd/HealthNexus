<?php

namespace Tests\Feature\Security;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** T-S5: an authenticated organization member without the permission gets 403. */
class ApiCollectionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithoutPermissions(): Organization
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'staff']);

        $this->withHeader('Authorization', 'Bearer '.$user->createToken('t')->plainTextToken);
        $this->withHeader('X-Organization-Id', (string) $organization->id);

        return $organization;
    }

    public function test_listing_endpoints_require_the_view_permission(): void
    {
        $this->memberWithoutPermissions();

        foreach ([
            '/api/v1/patients', '/api/v1/patients/search?q=a', '/api/v1/patients/duplicates',
            '/api/v1/appointments', '/api/v1/appointments/search', '/api/v1/appointments/calendar',
            '/api/v1/encounters', '/api/v1/providers',
        ] as $url) {
            $this->getJson($url)->assertForbidden();
        }
    }

    public function test_user_without_permission_cannot_create_an_organization(): void
    {
        $this->memberWithoutPermissions();

        $this->postJson('/api/v1/organizations', [
            'name' => 'Rogue', 'code' => 'ROGUE', 'slug' => 'rogue',
        ])->assertForbidden();
    }
}
