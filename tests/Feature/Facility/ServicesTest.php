<?php

namespace Tests\Feature\Facility;

use App\Models\Ed\EdVisit;
use App\Models\Encounter;
use App\Models\Facility\FacAmbulance;
use App\Models\Facility\FacBatchItem;
use App\Models\Facility\FacDietOrder;
use App\Models\Facility\FacDietType;
use App\Models\Facility\FacGateLog;
use App\Models\Facility\FacInstrumentSet;
use App\Models\Facility\FacLinenType;
use App\Models\Facility\FacMealTray;
use App\Models\Facility\FacMortuaryCase;
use App\Models\Facility\FacMortuaryUnit;
use App\Models\Facility\FacVisitorPass;
use App\Models\Facility\FacWasteRecord;
use App\Models\Patient;
use App\Services\Facility\AmbulanceService;
use App\Services\Facility\AssetService;
use App\Services\Facility\CssdService;
use App\Services\Facility\DietService;
use App\Services\Facility\FacilityCheck;
use App\Services\Facility\LinenService;
use App\Services\Facility\MortuaryService;
use App\Services\Facility\VisitorService;
use App\Services\Facility\WasteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicesTest extends FacilityTestCase
{
    private function surgery(): int
    {
        $p = Patient::factory()->create(['organization_id' => $this->organization->id]);

        $e = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $p->id]);

        return DB::table('ot_surgeries')->insertGetId(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'surgery_number' => 'OT-'.uniqid(), 'patient_id' => $p->id, 'encounter_id' => $e->id, 'status' => 'closed', 'created_at' => now(), 'updated_at' => now()]);
    }

    // ---- sterile services -------------------------------------------------------------------------------------------------------------------------------------------

    public function test_only_a_passed_batch_released_by_a_second_person_gives_packs_that_can_be_used(): void
    {
        $svc = app(CssdService::class);
        $op = $this->actor(['facility.cssd.manage']);
        $other = $this->actor(['facility.cssd.release']);
        $set = FacInstrumentSet::query()->where('code', 'MINOR')->first();
        $batch = $svc->load($this->organization->id, 'steam', [$set->id, $set->id], $op);
        $this->assertMatchesRegularExpression('/^STB-2026-\d{5}$/', $batch->number);
        $pack = $batch->items()->first();
        try {
            $svc->issue($pack, $this->surgery(), $op);
            $this->fail('an unreleased pack must not go out');
        } catch (ValidationException) {
        }
        $svc->start($batch);
        $svc->finish($batch->fresh(), 'pass', 134, 205, 18);
        try {
            $svc->release($batch->fresh(), $op);
            $this->fail('the operator cannot release their own batch');
        } catch (ValidationException) {
        }
        $svc->release($batch->fresh(), $other);
        $this->assertSame('2026-10-10', $pack->fresh()->use_by->toDateString(), 'steam: 30 days');
        $surgery = $this->surgery();
        $svc->issue($pack->fresh(), $surgery, $op);
        $this->assertSame([$surgery], array_column($svc->trace($batch->fresh()), 'surgery_id'));
        $this->assertSame($pack->id, $svc->packsUsedIn($surgery)[0]->id);
        try {
            $svc->issue($pack->fresh(), $this->surgery(), $op);
            $this->fail('a pack is used once');
        } catch (ValidationException) {
        }
    }

    public function test_a_failed_batch_is_withdrawn_and_a_recall_lists_the_patients_already_touched(): void
    {
        $svc = app(CssdService::class);
        $op = $this->actor(['facility.cssd.manage']);
        $other = $this->actor(['facility.cssd.release']);
        $set = FacInstrumentSet::query()->where('code', 'DRESS')->first();
        $bad = $svc->load($this->organization->id, 'steam', [$set->id], $op);
        $svc->start($bad);
        $svc->finish($bad->fresh(), 'fail', 120, 150, 10);
        $this->assertSame('withdrawn', $bad->items()->first()->status);
        try {
            $svc->release($bad->fresh(), $other);
            $this->fail('a failed batch cannot be released');
        } catch (ValidationException) {
        }
        $good = $svc->load($this->organization->id, 'eto', [$set->id, $set->id], $op);
        $svc->start($good);
        $svc->finish($good->fresh(), 'pass', null, null, 240);
        $svc->release($good->fresh(), $other);
        $s = $this->surgery();
        $svc->issue($good->items()->first(), $s, $op);
        $used = $svc->recall($good->fresh(), $other, 'The chemical indicator lot was found faulty.');
        $this->assertSame([$s], array_column($used, 'surgery_id'));
        $this->assertSame('recalled', $good->fresh()->status);
        $this->assertSame(1, FacBatchItem::query()->where(['batch_id' => $good->id, 'status' => 'withdrawn'])->count(), 'the unused pack is withdrawn');
    }

    public function test_a_steriliser_that_is_out_of_service_cannot_be_loaded_and_old_packs_are_withdrawn(): void
    {
        $steriliser = app(AssetService::class)->register($this->organization->id, ['name' => 'Autoclave 1', 'category' => 'biomedical', 'risk_class' => 'medium'], $this->user);
        $steriliser->update(['status' => 'out_of_service']);
        $set = FacInstrumentSet::query()->first();
        try {
            app(CssdService::class)->load($this->organization->id, 'steam', [$set->id], $this->user, $steriliser);
            $this->fail('out of service');
        } catch (ValidationException) {
        }
        $batch = app(CssdService::class)->load($this->organization->id, 'plasma', [$set->id], $this->user);
        FacBatchItem::query()->where('batch_id', $batch->id)->update(['use_by' => '2026-09-01']);
        $this->assertSame(1, app(CssdService::class)->withdrawExpired($this->organization->id));
    }

    // ---- linen ----------------------------------------------------------------------------------------------------------------------------------------------------------

    public function test_linen_is_a_ledger_that_tracks_where_it_is_and_refuses_the_impossible(): void
    {
        $svc = app(LinenService::class);
        $sheet = FacLinenType::query()->where('code', 'SHEET')->first();
        $svc->move($sheet, 'receive_new', 150, $this->user);
        $svc->move($sheet, 'issue', 100, $this->user, 'Ward 2');
        $svc->move($sheet, 'collect_soiled', 90, $this->user, 'Ward 2');
        $svc->move($sheet, 'send_laundry', 80, $this->user);
        $svc->move($sheet, 'condemn', 5, $this->user);
        $svc->move($sheet, 'receive_clean', 60, $this->user);
        $this->assertSame(['clean_store' => 110, 'on_wards' => 10, 'soiled' => 5, 'at_laundry' => 20, 'condemned' => 5, 'total' => 145], $svc->balance($sheet));
        foreach ([['issue', 111], ['collect_soiled', 11], ['send_laundry', 6], ['receive_clean', 21], ['condemn', 6], ['magic', 1], ['issue', 0]] as [$m, $q]) {
            try {
                $svc->move($sheet, $m, $q, $this->user);
                $this->fail("accepted $m $q");
            } catch (ValidationException) {
            }
        }
        try {
            DB::table('fac_linen_movements')->limit(1)->update(['quantity' => 1]);
            $this->fail('the ledger was changed');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('only grows', $e->getMessage());
        }
    }

    public function test_clean_stock_below_the_level_warns(): void
    {
        $svc = app(LinenService::class);
        $this->actor(['facility.linen.manage']);
        $gown = FacLinenType::query()->where('code', 'GOWN')->first(); // level 90
        $svc->move($gown, 'receive_new', 50, $this->user);
        $this->assertNotNull(DB::table('clinical_alerts')->where('category', 'facility')->first());
    }

    // ---- waste ----------------------------------------------------------------------------------------------------------------------------------------------------------

    public function test_a_waste_manifest_collects_the_records_and_must_add_up(): void
    {
        $svc = app(WasteService::class);
        $svc->record($this->organization->id, 'infectious', 'Ward 3', 12.5, $this->user, '2026-09-08');
        $svc->record($this->organization->id, 'sharps', 'OT', 3.25, $this->user, '2026-09-09');
        $svc->record($this->organization->id, 'general', 'Kitchen', 40, $this->user, '2026-09-10');
        foreach ([['plastic', 'x', 1], ['sharps', 'x', 0], ['sharps', '', 2]] as [$c, $l, $kg]) {
            try {
                $svc->record($this->organization->id, $c, $l, $kg, $this->user);
                $this->fail('accepted');
            } catch (ValidationException) {
            }
        }
        $m = $svc->manifest($this->organization->id, 'Green Disposal Ltd', 'DHA-11-4455', '2026-09-09');
        $this->assertSame('15.75', $m->total_kg, 'only what had been recorded by that date');
        $this->assertSame(1, FacWasteRecord::query()->whereNull('manifest_id')->count());
        try {
            $svc->confirm($m, 'DC-1');
            $this->fail('hand it over first');
        } catch (ValidationException) {
        }
        FacWasteRecord::query()->where('manifest_id', $m->id)->first()->update(['weight_kg' => 99]);
        try {
            $svc->handOver($m->fresh(), $this->user);
            $this->fail('the manifest no longer matches its records');
        } catch (ValidationException) {
        }
        FacWasteRecord::query()->where('weight_kg', 99)->update(['weight_kg' => 12.5]);
        $svc->handOver($m->fresh(), $this->user);
        $svc->confirm($m->fresh(), 'DC-2026-77', '2026-09-10');
        $this->assertSame('confirmed', $m->fresh()->status);
        $totals = $svc->totals($this->organization->id, '2026-09-01', '2026-09-30');
        ksort($totals);
        $this->assertSame(['general' => 40.0, 'infectious' => 12.5, 'sharps' => 3.25], $totals);
        $this->expectException(ValidationException::class);
        $svc->manifest($this->organization->id, 'Green Disposal Ltd', null, '2026-09-09'); // nothing left that old
    }

    // ---- diet -----------------------------------------------------------------------------------------------------------------------------------------------------------

    public function test_one_active_diet_per_patient_and_a_meal_list_made_from_the_orders(): void
    {
        $svc = app(DietService::class);
        $a1 = $this->admit();
        $a2 = $this->admit();
        $cook = $this->actor(['facility.diet.kitchen']);
        $diabetic = FacDietType::query()->where('code', 'DIABETIC')->first();
        $normal = FacDietType::query()->where('code', 'NORMAL')->first();
        $first = $svc->order($this->organization->id, $a1->id, $normal, ['texture' => 'normal'], $this->user);
        $second = $svc->order($this->organization->id, $a1->id, $diabetic, ['texture' => 'soft', 'allergies' => 'Peanuts'], $this->user);
        $this->assertSame('stopped', $first->fresh()->status);
        $svc->order($this->organization->id, $a2->id, $normal, ['texture' => 'nil_by_mouth'], $this->user);
        $list = $svc->mealList($this->organization->id, '2026-09-10', 'lunch');
        $this->assertSame(2, $list->trays()->count());
        $this->assertSame(0, $svc->mealList($this->organization->id, '2026-09-10', 'lunch')->trays()->count() - 2, 'asking again adds nothing');
        $fasting = FacMealTray::query()->whereHas('order', fn ($q) => $q->where('texture', 'nil_by_mouth'))->first();
        $this->assertSame('held', $fasting->status);
        try {
            $svc->serve($fasting, 'served');
            $this->fail('a fasting patient is not served');
        } catch (ValidationException) {
        }
        try {
            $svc->closeList($list, $cook);
            $this->fail('a tray is still planned');
        } catch (ValidationException) {
        }
        $svc->serve(FacMealTray::query()->where('diet_order_id', $second->id)->first(), 'refused', 'Not hungry');
        $svc->closeList($list->fresh(), $cook);
        $this->assertSame('served', $list->fresh()->status);
        $this->assertSame(['Diabetic diet / soft' => ['breakfast' => 1, 'lunch' => 1, 'snack' => 1, 'dinner' => 1]], $svc->requisition($this->organization->id, '2026-09-10'));
    }

    public function test_diets_end_when_the_patient_goes_home_and_only_for_patients_in_hospital(): void
    {
        $svc = app(DietService::class);
        $a = $this->admit();
        $o = $svc->order($this->organization->id, $a->id, FacDietType::query()->first(), [], $this->user);
        DB::table('ipd_admissions')->where('id', $a->id)->update(['actual_discharge_date' => now(), 'status' => 'discharged']);
        $this->assertSame(1, app(FacilityCheck::class)->run($this->organization->id)['diets_ended']);
        $this->assertSame('stopped', FacDietOrder::find($o->id)->status);
        $this->expectException(ValidationException::class);
        $svc->order($this->organization->id, $a->id, FacDietType::query()->first(), [], $this->user);
    }

    // ---- ambulances ----------------------------------------------------------------------------------------------------------------------------------------------------------

    public function test_a_trip_goes_from_request_to_completion_and_a_busy_vehicle_cannot_be_sent(): void
    {
        $svc = app(AmbulanceService::class);
        $a = $svc->add($this->organization->id, 'dhaka metro-ha-11-2233', 'advanced', null, 41000);
        $b = $svc->add($this->organization->id, 'DHAKA METRO-HA-11-4455', 'basic', null, 9000);
        $trip = $svc->request($this->organization->id, ['kind' => 'emergency', 'from_place' => 'Mirpur 10', 'to_place' => 'Emergency department'], $this->user);
        $trip2 = $svc->request($this->organization->id, ['kind' => 'transfer', 'from_place' => 'Ward 3', 'to_place' => 'City Heart Hospital'], $this->user);
        Carbon::setTestNow('2026-09-10 10:06:00');
        $svc->dispatch($trip, $a, 'Rahim');
        $this->assertSame('on_trip', $a->fresh()->status);
        try {
            $svc->dispatch($trip2, $a->fresh(), 'Karim');
            $this->fail('the vehicle is busy');
        } catch (ValidationException) {
        }
        try {
            $svc->complete($trip->fresh(), 40000);
            $this->fail('odometer went back');
        } catch (ValidationException) {
        }
        $svc->arrive($trip->fresh());
        $svc->complete($trip->fresh(), 41018);
        $this->assertSame(['available', 41018], [$a->fresh()->status, $a->fresh()->odometer_km]);
        $this->assertSame(6.0, $svc->averageResponseMinutes($this->organization->id, '2026-09-01', '2026-09-30'));
        $svc->setMaintenance($b, true);
        try {
            $svc->dispatch($trip2->fresh(), $b->fresh(), 'Karim');
            $this->fail('in maintenance');
        } catch (ValidationException) {
        }
        $svc->cancel($trip2->fresh(), 'The family took the patient in their own car.');
        $this->assertSame('cancelled', $trip2->fresh()->status);
        $this->assertSame('DHAKA METRO-HA-11-2233', FacAmbulance::query()->first()->registration_no);
    }

    // ---- mortuary ------------------------------------------------------------------------------------------------------------------------------------------------------------

    private function death(): FacMortuaryCase
    {
        $visit = EdVisit::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'status' => 'dispositioned', 'disposition_type' => 'death', 'dispositioned_at' => '2026-09-09 12:00:00']);
        $visit->patient()->update(['status' => 'deceased', 'deceased_at' => '2026-09-09 11:50:00']);
        app(MortuaryService::class)->sweep($this->organization->id);

        return FacMortuaryCase::query()->latest('id')->firstOrFail();
    }

    public function test_each_death_gets_one_case_two_people_check_identity_and_a_unit_has_a_limit(): void
    {
        $svc = app(MortuaryService::class);
        $case = $this->death();
        $this->assertSame(0, $svc->sweep($this->organization->id), 'once');
        $this->assertMatchesRegularExpression('/^MC-2026-\d{5}$/', $case->number);
        $a = $this->actor(['facility.mortuary.manage']);
        $b = $this->actor(['facility.mortuary.manage']);
        $unit = FacMortuaryUnit::query()->where('code', 'MC-1')->first();
        try {
            $svc->receive($case, $a, $a, 'T-1', $unit);
            $this->fail('a second person must check');
        } catch (ValidationException) {
        }
        $svc->receive($case, $a, $b, 'T-1001', $unit);
        $this->assertSame(['stored', $b->id], [$case->fresh()->status, $case->fresh()->verified_by]);
        $unit->update(['capacity' => 1]);
        $second = FacMortuaryCase::create(['organization_id' => $this->organization->id, 'number' => 'MC-X', 'patient_id' => Patient::factory()->create(['organization_id' => $this->organization->id])->id, 'source_type' => 'ed_visit', 'source_id' => 99, 'died_at' => now()->subHour(), 'status' => 'pending']);
        $this->expectException(ValidationException::class);
        $svc->receive($second, $a, $b, 'T-1002', $unit->fresh());
    }

    public function test_release_needs_papers_a_cleared_police_case_and_a_second_person(): void
    {
        $svc = app(MortuaryService::class);
        $case = $this->death();
        $a = $this->actor(['facility.mortuary.manage']);
        $b = $this->actor(['facility.mortuary.release']);
        $svc->receive($case, $a, $b, 'T-2001', FacMortuaryUnit::query()->first(), true);
        try {
            $svc->requestRelease($case->fresh(), $a, 'Abdul Karim', 'Brother', 'national_id', '1990123456789');
            $this->fail('a police case is held');
        } catch (ValidationException) {
        }
        $svc->clearPolice($case->fresh(), $a);
        foreach ([['', 'Brother', 'national_id', '1990123456789'], ['Abdul Karim', 'Brother', 'selfie', '1990123456789'], ['Abdul Karim', 'Brother', 'national_id', '12']] as [$to, $rel, $type, $num]) {
            try {
                $svc->requestRelease($case->fresh(), $a, $to, $rel, $type, $num);
                $this->fail('accepted');
            } catch (ValidationException) {
            }
        }
        $svc->requestRelease($case->fresh(), $a, 'Abdul Karim', 'Brother', 'national_id', '1990123456789');
        try {
            $svc->authoriseRelease($case->fresh(), $a);
            $this->fail('the person who asked cannot authorise');
        } catch (ValidationException) {
        }
        $svc->authoriseRelease($case->fresh(), $b);
        $done = $case->fresh();
        $this->assertSame(['released', $b->id], [$done->status, $done->authorised_by]);
        $this->assertSame('1990123456789', $done->id_number);
        $this->assertStringNotContainsString('1990123456789', (string) DB::table('fac_mortuary_cases')->where('id', $done->id)->value('id_number'), 'the paper number is stored encrypted');
        $this->assertSame('•••••••••6789', $svc->maskedId($done->id_number));
    }

    // ---- visitors ------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function test_passes_follow_the_rules_for_visitors_attendants_and_blocked_people(): void
    {
        $svc = app(VisitorService::class);
        $adm = $this->admit();
        $guard = $this->actor(['facility.visitor.gate']);
        $at = fn (string $kind, string $name, array $o = []) => $svc->issue($this->organization->id, $adm->id, array_merge(['kind' => $kind, 'visitor_name' => $name, 'phone' => '0171'.random_int(1000000, 9999999)], $o), $this->user);
        $att = $at('attendant', 'Rina Begum', ['id_type' => 'national_id', 'id_number' => '19900011223']);
        try {
            $at('attendant', 'Second Person', ['id_type' => 'national_id', 'id_number' => '19900011224']);
            $this->fail('one attendant at a time');
        } catch (ValidationException) {
        }
        try {
            $at('attendant', 'No Papers');
            $this->fail('an attendant shows papers');
        } catch (ValidationException) {
        }
        $svc->block($this->organization->id, 'Mr Troublemaker', null, 'Threatened a nurse on 2 September.', $this->user);
        try {
            $at('visitor', 'mr troublemaker');
            $this->fail('blocked');
        } catch (ValidationException) {
        }
        $this->assertNotNull($svc->enter($att, $guard, 'Main gate'), 'an attendant may come at any hour');
        $this->assertTrue($svc->inside($att));
        $this->assertSame('•••••••1223', $svc->maskedId($att->id_number));
        $this->assertNotSame('19900011223', DB::table('fac_visitor_passes')->where('id', $att->id)->value('id_number'));
    }

    public function test_visitors_are_let_in_only_in_visiting_hours_and_not_too_many_at_once(): void
    {
        $svc = app(VisitorService::class);
        Carbon::setTestNow('2026-09-10 05:00:00'); // 11:00 in Dhaka: visiting hours
        $adm = $this->admit();
        $guard = $this->actor(['facility.visitor.gate']);
        $passes = collect(['A One', 'B Two', 'C Three'])->map(fn ($n) => $svc->issue($this->organization->id, $adm->id, ['kind' => 'visitor', 'visitor_name' => $n], $this->user));
        $svc->enter($passes[0], $guard);
        $svc->enter($passes[1], $guard);
        try {
            $svc->enter($passes[2], $guard);
            $this->fail('two are already inside');
        } catch (ValidationException) {
        }
        $this->assertSame('refused', FacGateLog::query()->orderByDesc('id')->first()->outcome, 'the refusal is logged too');
        $svc->leave($passes[0], $guard);
        $svc->enter($passes[2], $guard);
        try {
            $svc->enter($passes[2], $guard);
            $this->fail('already inside');
        } catch (ValidationException) {
        }
        Carbon::setTestNow('2026-09-10 07:30:00'); // 13:30: outside hours
        $this->assertSame('It is outside visiting hours.', $svc->refusal($passes[0]));
    }

    public function test_passes_end_with_the_stay_and_the_gate_log_only_grows(): void
    {
        $svc = app(VisitorService::class);
        $adm = $this->admit();
        $pass = $svc->issue($this->organization->id, $adm->id, ['kind' => 'visitor', 'visitor_name' => 'Karim Uddin'], $this->user);
        DB::table('ipd_admissions')->where('id', $adm->id)->update(['actual_discharge_date' => now(), 'status' => 'discharged']);
        $this->assertSame(1, $svc->expireEnded($this->organization->id));
        $this->assertSame('expired', FacVisitorPass::find($pass->id)->status);
        $this->assertStringContainsString('expired', (string) $svc->refusal(FacVisitorPass::find($pass->id)));
        $guard = $this->actor(['facility.visitor.gate']);
        try {
            $svc->enter(FacVisitorPass::find($pass->id), $guard);
        } catch (ValidationException) {
        }
        try {
            DB::table('fac_gate_logs')->delete();
            $this->fail('the log was cleared');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('only grows', $e->getMessage());
        }
        $this->expectException(ValidationException::class);
        $svc->issue($this->organization->id, $adm->id, ['kind' => 'visitor', 'visitor_name' => 'Late Comer'], $this->user); // the patient has gone home
    }
}
