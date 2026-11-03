<?php

namespace Tests\Feature\Security;

use App\Models\Concerns\HasPublicId;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientAlert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/** H-12 gate: a record about a patient never appears in a URL by its numeric id (guideline 9.1). New modules fail this test until they use HasPublicId. */
class PublicIdCoverageTest extends TestCase
{
    use RefreshDatabase;

    /** Records that carry a patient_id but are configuration or links, not records about a patient. */
    private const EXEMPT = [];

    public function test_every_patient_record_bound_in_a_route_uses_a_public_id(): void
    {
        $offenders = [];
        $checked = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction('uses');
            if (! is_string($action) || ! str_contains($action, '@')) {
                continue;
            }
            [$class, $method] = explode('@', $action);
            if (! class_exists($class) || ! method_exists($class, $method)) {
                continue;
            }

            foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
                $type = $parameter->getType();
                if (! $type || $type->isBuiltin() || ! is_subclass_of($type->getName(), Model::class) || ! in_array($parameter->getName(), $route->parameterNames(), true)) {
                    continue;
                }

                $model = $type->getName();
                $checked[$model] = true;
                $aboutAPatient = Schema::hasColumn((new $model)->getTable(), 'patient_id');

                if ($aboutAPatient && ! in_array($model, self::EXEMPT, true) && ! in_array(HasPublicId::class, class_uses_recursive($model), true)) {
                    $offenders[$model] = $route->uri();
                }
            }
        }

        $this->assertGreaterThan(50, count($checked));
        $this->assertSame([], $offenders, 'Add HasPublicId + a public_id migration for: '.implode(', ', array_keys($offenders)));
    }

    public function test_a_row_written_without_the_model_gets_a_public_id_when_it_is_first_linked(): void
    {
        $organization = Organization::factory()->create();
        $patient = Patient::factory()->create(['organization_id' => $organization->id]);
        DB::table('patient_alerts')->insert([
            'organization_id' => $organization->id, 'patient_id' => $patient->id, 'alert_type' => 'clinical', 'title' => 'Raw insert',
            'severity' => 'warning', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $alert = PatientAlert::firstOrFail();
        $this->assertNull($alert->getRawOriginal('public_id'));

        $key = $alert->getRouteKey();

        $this->assertSame(26, strlen($key));
        $this->assertSame($key, PatientAlert::firstOrFail()->public_id);
    }
}
