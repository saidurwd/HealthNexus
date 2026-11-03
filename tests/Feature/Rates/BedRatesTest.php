<?php

namespace Tests\Feature\Rates;

use App\Models\Billing\BillingPriceList;
use App\Services\Rates\BedRateService;
use Carbon\CarbonImmutable;
use Tests\Feature\Dashboard\FrontDoorTestCase;

/** The bed and cabin rate card. Now is Thursday 12 Nov 2026, 10:00 in Dhaka. */
class BedRatesTest extends FrontDoorTestCase
{
    private function svc(): BedRateService
    {
        return app(BedRateService::class);
    }

    private function bedType(string $code, string $name): int
    {
        return $this->filled('ipd_bed_types', ['code' => $code, 'name' => $name, 'is_active' => true, 'branch_id' => null]);
    }

    /** @return int the Billing item that prices a day in this bed type */
    private function item(string $key, float $base, array $o = []): int
    {
        return $this->filled('billing_items', $o + ['item_code' => 'BED-'.strtoupper($key), 'name' => 'Bed day '.$key, 'base_price' => $base, 'is_clinically_chargeable' => true, 'clinical_event_type' => 'ipd_bed_day', 'clinical_event_key' => $key, 'is_active' => true, 'branch_id' => null]);
    }

    private function beds(int $typeId, int $n): void
    {
        $ward = $this->filled('ipd_wards', ['code' => 'W'.$typeId, 'name' => 'Ward '.$typeId, 'is_active' => true]);
        $room = $this->filled('ipd_rooms', ['ward_id' => $ward, 'room_number' => 'R'.$typeId, 'is_active' => true]);
        for ($i = 0; $i < $n; $i++) {
            $this->filled('ipd_beds', ['room_id' => $room, 'bed_code' => "B$typeId-$i", 'bed_type_id' => $typeId ?: null, 'status' => 'available', 'is_active' => true]);
        }
    }

    private function priceList(string $category, int $itemId, float $price, string $code = 'PL', array $o = []): void
    {
        $list = BillingPriceList::query()->firstOrCreate(['organization_id' => $this->organization->id, 'branch_id' => null, 'code' => $code], array_merge(['name' => 'List '.$code, 'currency' => 'BDT', 'status' => 'active', 'priority' => 50], $o));
        $this->filled('billing_price_list_items', ['price_list_id' => $list->id, 'billing_item_id' => $itemId, 'patient_category' => $category, 'scope_hash' => hash('sha1', "$itemId|null|null|null|null|$category"), 'unit_price' => $price, 'is_active' => true, 'organization_id' => null]);
    }

    public function test_the_card_prices_a_bed_type_as_billing_would(): void
    {
        $cabin = $this->bedType('CABIN', 'Private cabin');
        $item = $this->item('CABIN', 4000);
        $this->beds($cabin, 3);
        $this->priceList('corporate', $item, 3400);

        $card = $this->svc()->card($this->organization->id, null, CarbonImmutable::now());

        $row = $card['rows'][0];
        $this->assertSame(['corporate'], $card['categories']);
        $this->assertSame([3, '4000.00', 'item_base_price'], [$row['beds'], $row['prices']['']['price'], $row['prices']['']['source']], 'the standard price is the item\'s base price');
        $this->assertSame(['3400.00', 'price_list', 'List PL'], [$row['prices']['corporate']['price'], $row['prices']['corporate']['source'], $row['prices']['corporate']['list']]);
    }

    public function test_a_price_list_that_is_not_active_or_not_yet_in_force_is_ignored(): void
    {
        $t = $this->bedType('WARD', 'General ward');
        $item = $this->item('WARD', 1500);
        $this->beds($t, 1);
        $this->priceList('corporate', $item, 1000, 'OFF', ['status' => 'draft']);
        $this->priceList('staff', $item, 700, 'LATER', ['effective_from' => '2027-01-01']);

        $card = $this->svc()->card($this->organization->id, null, CarbonImmutable::now());

        $this->assertSame([], $card['categories'], 'no active price list offers a category');
        $this->assertSame('1500.00', $card['rows'][0]['prices']['']['price']);
    }

    public function test_a_bed_type_with_beds_but_no_billing_item_is_a_gap(): void
    {
        $t = $this->bedType('ICU', 'Intensive');
        $this->beds($t, 2);
        $quiet = $this->bedType('SPARE', 'Spare');                    // no beds, no item: not a worry
        $this->item('general', 1500);

        $g = $this->svc()->card($this->organization->id)['gaps'];

        $this->assertSame([['name' => 'Intensive', 'code' => 'ICU', 'beds' => 2]], $g['no_item']);
        $this->assertNotNull($quiet);
    }

    public function test_an_inactive_item_does_not_price_the_bed_and_another_hospitals_item_is_not_used(): void
    {
        $t = $this->bedType('WARD', 'General ward');
        $this->item('WARD', 1500, ['is_active' => false]);
        $other = $this->otherHospital();
        $this->filled('billing_items', ['organization_id' => $other->id, 'branch_id' => null, 'item_code' => 'X', 'name' => 'x', 'base_price' => 99, 'is_clinically_chargeable' => true, 'clinical_event_type' => 'ipd_bed_day', 'clinical_event_key' => 'WARD', 'is_active' => true]);
        $this->beds($t, 1);

        $this->assertNull($this->svc()->itemFor($this->organization->id, null, 'WARD'));
        $this->assertSame('WARD', $this->svc()->card($this->organization->id)['gaps']['no_item'][0]['code']);
    }

    public function test_beds_with_no_type_are_charged_as_general_and_warned_about_when_there_is_no_general_price(): void
    {
        $this->bedType('WARD', 'General ward');
        $this->item('WARD', 1500);
        $this->beds(0, 4);                                            // beds with no bed type

        $g = $this->svc()->card($this->organization->id)['gaps'];
        $this->assertSame([4, false], [$g['untyped_beds'], $g['untyped_priced']]);

        $this->item('general', 1500);
        $this->assertTrue($this->svc()->card($this->organization->id)['gaps']['untyped_priced']);
    }

    public function test_a_price_of_zero_and_an_item_for_a_bed_type_that_is_gone_are_flagged(): void
    {
        $this->bedType('FREE', 'Charity ward');
        $this->item('FREE', 0);
        $this->item('GHOST', 900);

        $g = $this->svc()->card($this->organization->id)['gaps'];

        $this->assertSame(['Charity ward'], $g['zero_price']);
        $this->assertArrayHasKey('BED-GHOST', $g['orphan_items']);
    }

    public function test_a_stay_is_the_days_times_the_day_rate_and_the_days_are_bounded(): void
    {
        $this->bedType('CABIN', 'Private cabin');
        $item = $this->item('CABIN', 4000);
        $this->priceList('corporate', $item, 3400);

        $std = $this->svc()->estimate($this->organization->id, null, 'CABIN', 3, null);
        $corp = $this->svc()->estimate($this->organization->id, null, 'CABIN', 3, 'corporate');
        $big = $this->svc()->estimate($this->organization->id, null, 'CABIN', 9999, null);

        $this->assertSame(['12000.00', '4000.00'], [$std['total'], $std['per_day']]);
        $this->assertSame('10200.00', $corp['total']);
        $this->assertSame(365, $big['days']);
        $this->assertNull($this->svc()->estimate($this->organization->id, null, 'NOPE', 2, null));
    }

    public function test_rooms_with_a_typed_label_are_listed_but_do_not_change_a_price(): void
    {
        $t = $this->bedType('WARD', 'General ward');
        $this->item('WARD', 1500);
        $ward = $this->filled('ipd_wards', ['code' => 'V', 'name' => 'VIP wing', 'is_active' => true]);
        $this->filled('ipd_rooms', ['ward_id' => $ward, 'room_number' => 'V1', 'is_active' => true, 'rate_category' => 'Deluxe', 'is_vip' => true]);

        $labelled = $this->svc()->labelledRooms($this->organization->id);

        $this->assertSame([['ward' => 'VIP wing', 'room' => 'V1', 'label' => 'Deluxe', 'vip' => true]], $labelled);
        $this->assertSame('1500.00', $this->svc()->card($this->organization->id)['rows'][0]['prices']['']['price']);
        $this->assertNotNull($t);
    }

    public function test_the_page_needs_the_right_states_its_question_and_prints(): void
    {
        $this->bedType('WARD', 'General ward');
        $this->item('WARD', 1500);

        $this->as($this->actor(['patients.view']))->get('/admin/rates/beds')->assertForbidden();
        $html = $this->as($this->actor(['rates.bed.view']))->get('/admin/rates/beds')->assertOk()->getContent();

        $this->assertStringContainsString('data-dashboard-question', $html);
        $this->assertStringContainsString('General ward', $html);
        $this->assertStringContainsString('Print the rate card', $html);
        $this->assertStringNotContainsString('Change prices in Billing', $html, 'no billing right, no link');
    }

    public function test_the_estimate_form_works_from_the_page(): void
    {
        $this->bedType('CABIN', 'Private cabin');
        $this->item('CABIN', 4000);

        $html = $this->as($this->actor(['rates.bed.view']))->get('/admin/rates/beds?bed_type=CABIN&days=2')->assertOk()->getContent();
        $this->assertStringContainsString('2 ×', $html);
        $this->as($this->actor(['rates.bed.view']))->get('/admin/rates/beds?bed_type=CABIN&days=0')->assertSessionHasErrors('days');
        $this->assertStringContainsString('has no price in Billing', $this->as($this->actor(['rates.bed.view']))->get('/admin/rates/beds?bed_type=NOPE&days=2')->getContent());
    }
}
