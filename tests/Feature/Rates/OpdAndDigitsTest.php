<?php

namespace Tests\Feature\Rates;

use App\Models\Billing\BillingPriceList;
use App\Services\Rates\BedRateService;
use App\Support\Dashboard\Numbers;
use Carbon\CarbonImmutable;
use Tests\Feature\Dashboard\FrontDoorTestCase;

/** The OPD ticket and fees card, and a person's own choice of Bangla digits (Phase 26, B7). */
class OpdAndDigitsTest extends FrontDoorTestCase
{
    private function fee(string $code, float $price, array $o = []): int
    {
        return $this->filled('billing_items', $o + ['item_code' => $code, 'name' => 'Fee '.$code, 'base_price' => $price, 'is_active' => true, 'branch_id' => null]);
    }

    private function listed(string $category, int $itemId, float $price): void
    {
        $list = BillingPriceList::query()->firstOrCreate(['organization_id' => $this->organization->id, 'branch_id' => null, 'code' => 'PL'], ['name' => 'Government rates', 'currency' => 'BDT', 'status' => 'active', 'priority' => 50]);
        $this->filled('billing_price_list_items', ['price_list_id' => $list->id, 'billing_item_id' => $itemId, 'patient_category' => $category, 'scope_hash' => hash('sha1', "$itemId|null|null|null|null|$category"), 'unit_price' => $price, 'is_active' => true, 'organization_id' => null]);
    }

    public function test_the_ticket_card_shows_each_fee_per_category_and_a_zero_as_free(): void
    {
        $ticket = $this->fee('OPD-CONSULT', 5);
        $this->fee('REGISTRATION', 100);
        $this->listed('poor', $ticket, 0);

        $card = app(BedRateService::class)->opdCard($this->organization->id, null, CarbonImmutable::now());

        $this->assertSame(['poor'], $card['categories']);
        $byCode = collect($card['rows'])->keyBy(fn ($r) => $r['item']->item_code);
        $this->assertSame(['5.00', '0.00', '100.00'], [$byCode['OPD-CONSULT']['prices']['']['price'], $byCode['OPD-CONSULT']['prices']['poor']['price'], $byCode['REGISTRATION']['prices']['']['price']]);
        $this->assertSame(['FOLLOWUP-CONSULT'], $card['missing'], 'the follow-up fee is not set up and is said so');
    }

    public function test_the_page_writes_free_for_zero_names_missing_fees_and_needs_the_right(): void
    {
        $ticket = $this->fee('OPD-CONSULT', 5);
        $this->listed('poor', $ticket, 0);
        $this->as($this->actor(['patients.view']))->get('/admin/rates/opd')->assertForbidden();

        $html = $this->as($this->actor(['rates.opd.view']))->get('/admin/rates/opd')->assertOk()->getContent();

        $this->assertStringContainsString('data-dashboard-question', $html);
        $this->assertStringContainsString('Free', $html);
        $this->assertStringContainsString('REGISTRATION', $html, 'the missing fee is named');
        $this->assertStringContainsString('Government rates', $html);
    }

    public function test_another_hospitals_fee_is_not_shown(): void
    {
        $other = $this->otherHospital();
        $this->filled('billing_items', ['organization_id' => $other->id, 'branch_id' => null, 'item_code' => 'OPD-CONSULT', 'name' => 'Theirs', 'base_price' => 1, 'is_active' => true]);

        $card = app(BedRateService::class)->opdCard($this->organization->id);

        $this->assertSame([], $card['rows']);
    }

    public function test_a_persons_own_choice_of_digits_beats_the_hospitals_and_null_follows_it(): void
    {
        app()->setLocale('bn');
        config(['ux.numbers.bangla_digits' => true]);
        $user = $this->actor([]);

        $this->actingAs($user);
        $this->assertSame('১২৩', Numbers::number(123), 'no choice: the hospital\'s setting (on) decides');
        $user->update(['bangla_digits' => false]);
        $this->assertSame('123', Numbers::number(123), 'the person turned them off');

        config(['ux.numbers.bangla_digits' => false]);
        $user->update(['bangla_digits' => true]);
        $this->assertSame('১২৩', Numbers::number(123), 'the person turned them on while the hospital has them off');
        $user->update(['bangla_digits' => null]);
        $this->assertSame('123', Numbers::number(123));
    }

    public function test_the_choice_only_applies_in_the_bangla_interface(): void
    {
        app()->setLocale('en');
        $user = $this->actor([]);
        $user->update(['bangla_digits' => true]);
        $this->actingAs($user);

        $this->assertSame('123', Numbers::number(123));
    }

    public function test_the_display_settings_page_saves_language_and_digits_for_this_person_only(): void
    {
        $me = $this->actor([]);
        $you = $this->actor([]);

        $this->as($me)->get('/preferences')->assertOk()->assertSee('Digits in the Bangla screens');
        $this->as($me)->post('/preferences', ['locale' => 'en', 'digits' => 'bangla'])->assertRedirect('/preferences');

        $this->assertSame(['en', true, null], [$me->refresh()->locale, $me->bangla_digits, $you->refresh()->bangla_digits]);
        $this->as($me)->post('/preferences', ['locale' => 'en', 'digits' => 'hospital']);
        $this->assertNull($me->refresh()->bangla_digits);
        $this->as($me)->post('/preferences', ['locale' => 'xx', 'digits' => 'latin'])->assertSessionHasErrors('locale');
        $this->as($me)->post('/preferences', ['locale' => 'en', 'digits' => 'maybe'])->assertSessionHasErrors('digits');
    }
}
