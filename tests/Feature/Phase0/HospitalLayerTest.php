<?php

namespace Tests\Feature\Phase0;

use App\Models\Branch;
use App\Models\Hospital;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_branch_created_without_a_hospital_joins_the_organizations_default_hospital(): void
    {
        $organization = Organization::factory()->create();

        $first = Branch::factory()->create(['organization_id' => $organization->id]);
        $second = Branch::factory()->create(['organization_id' => $organization->id]);

        $this->assertNotNull($first->hospital_id);
        $this->assertSame($first->hospital_id, $second->hospital_id);
        $this->assertSame(1, Hospital::where('organization_id', $organization->id)->count());
    }

    public function test_an_organization_can_have_several_hospitals(): void
    {
        $organization = Organization::factory()->create();
        $a = Hospital::factory()->create(['organization_id' => $organization->id]);
        $b = Hospital::factory()->create(['organization_id' => $organization->id]);

        $branch = Branch::factory()->create(['organization_id' => $organization->id, 'hospital_id' => $b->id]);

        $this->assertTrue($branch->hospital->is($b));
        $this->assertCount(2, $organization->hospitals);
        $this->assertCount(0, $a->branches);
    }
}
