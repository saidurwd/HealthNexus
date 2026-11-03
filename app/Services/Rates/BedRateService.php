<?php

namespace App\Services\Rates;

use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPriceList;
use App\Models\Billing\BillingPriceListItem;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedType;
use App\Models\Ipd\IpdRoom;
use App\Services\Billing\BillingPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The daily rate of each kind of bed, read from Billing and changed nowhere here. A bed-day charge is made by the ward service for the bed's TYPE: Billing is asked for the active, clinically chargeable item for the "ipd_bed_day" event whose key is the
 * bed type's code (a bed with no type is charged as "general"), and the price is worked out by Billing's own rules for the patient's category. This class asks Billing the same questions, so the card shows what a patient would really be charged,
 * and it lists what would NOT be: a bed type with no item gets no charge at all.
 */
class BedRateService
{
    public function __construct(private readonly BillingPricingService $pricing) {}

    /** The Billing item that prices a bed type's day, as the ward service would find it. */
    public function itemFor(int $organizationId, ?int $branchId, string $bedTypeCode): ?BillingItem
    {
        return BillingItem::query()->forTenant($organizationId, $branchId)->where('is_clinically_chargeable', true)->where('clinical_event_type', 'ipd_bed_day')->where('clinical_event_key', $bedTypeCode)->where('is_active', true)->first();
    }

    /** @return list<string> the patient categories that some price list gives its own price for, besides the standard one */
    public function categories(int $organizationId, ?int $branchId, ?CarbonImmutable $on = null): array
    {
        $day = ($on ?? CarbonImmutable::now())->toDateString();
        $lists = BillingPriceList::query()->forTenant($organizationId, $branchId)->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $day))->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $day))->pluck('id');

        return BillingPriceListItem::query()->whereIn('price_list_id', $lists)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $day))->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $day))
            ->whereNotNull('patient_category')->distinct()->orderBy('patient_category')->pluck('patient_category')->all();
    }

    /**
     * What one day in this item costs for a patient category on a date, and where the price comes from.
     *
     * @return array{price:string,source:string,list:?string}
     */
    public function priceOf(BillingItem $item, ?string $category, CarbonImmutable $on): array
    {
        $r = $this->pricing->resolvePrice($item, ['organization_id' => (int) $item->organization_id, 'branch_id' => $item->branch_id, 'patient_category' => $category, 'date' => $on->toDateString()]);
        $list = $r['price_list_item_id'] ? BillingPriceList::query()->whereKey(BillingPriceListItem::query()->whereKey($r['price_list_item_id'])->value('price_list_id'))->value('name') : null;

        return ['price' => number_format((float) $r['unit_price'], 2, '.', ''), 'source' => $r['source'], 'list' => $list];
    }

    /**
     * The rate card: a row per bed type with its item, its beds and its price for each category; and the gaps.
     *
     * @return array{rows:list<array<string,mixed>>,categories:list<string>,gaps:array<string,mixed>,on:string}
     */
    public function card(int $organizationId, ?int $branchId = null, ?CarbonImmutable $on = null): array
    {
        $on ??= CarbonImmutable::now();
        $categories = $this->categories($organizationId, $branchId, $on);
        $columns = array_merge([null], $categories);
        $beds = IpdBed::query()->where('organization_id', $organizationId)->where('is_active', true)->selectRaw('bed_type_id, count(*) as n')->groupBy('bed_type_id')->pluck('n', 'bed_type_id');
        $types = IpdBedType::query()->where('organization_id', $organizationId)->where('is_active', true)->orderBy('name')->get();
        $rows = [];
        foreach ($types as $t) {
            $item = $this->itemFor($organizationId, $branchId, $t->code);
            $prices = [];
            foreach ($columns as $cat) {
                $prices[$cat ?? ''] = $item ? $this->priceOf($item, $cat, $on) : null;
            }
            $rows[] = ['type' => $t, 'item' => $item, 'beds' => (int) ($beds[$t->id] ?? 0), 'prices' => $prices];
        }
        $general = $this->itemFor($organizationId, $branchId, 'general');
        $untyped = (int) ($beds->get('') ?? 0);
        $typeCodes = $types->pluck('code')->all();
        $orphans = BillingItem::query()->forTenant($organizationId, $branchId)->where('clinical_event_type', 'ipd_bed_day')->where('is_active', true)->whereNotIn('clinical_event_key', array_merge($typeCodes, ['general']))->pluck('name', 'item_code')->all();

        return [
            'rows' => $rows, 'categories' => $categories, 'on' => $on->toDateString(),
            'gaps' => [
                'no_item' => collect($rows)->filter(fn ($r) => $r['item'] === null && $r['beds'] > 0)->map(fn ($r) => ['name' => $r['type']->name, 'code' => $r['type']->code, 'beds' => $r['beds']])->values()->all(),
                'untyped_beds' => $untyped, 'untyped_priced' => $general !== null,
                'zero_price' => collect($rows)->filter(fn ($r) => $r['item'] !== null && collect($r['prices'])->contains(fn ($p) => $p !== null && (float) $p['price'] <= 0))->map(fn ($r) => $r['type']->name)->values()->all(),
                'orphan_items' => $orphans,
                'labels' => IpdRoom::query()->where('organization_id', $organizationId)->where('is_active', true)->whereNotNull('rate_category')->where('rate_category', '!=', '')->count(),
            ],
        ];
    }

    /**
     * What a stay costs at a bed type's daily rate. Only the bed: services, medicines and tests are billed on their own.
     *
     * @return array{days:int,per_day:string,total:string,source:string,list:?string}|null null when the bed type has no item
     */
    public function estimate(int $organizationId, ?int $branchId, string $bedTypeCode, int $days, ?string $category, ?CarbonImmutable $on = null): ?array
    {
        $item = $this->itemFor($organizationId, $branchId, $bedTypeCode);
        if ($item === null) {
            return null;
        }
        $p = $this->priceOf($item, $category, $on ?? CarbonImmutable::now());
        $days = max(1, min(365, $days));

        return ['days' => $days, 'per_day' => $p['price'], 'total' => bcmul($p['price'], (string) $days, 2), 'source' => $p['source'], 'list' => $p['list']];
    }

    /** Rooms with a rate label typed on them (it does not change any price - the bed type does). @return list<array{ward:string,room:string,label:string,vip:bool}> */
    public function labelledRooms(int $organizationId): array
    {
        return DB::table('ipd_rooms as r')->join('ipd_wards as w', 'w.id', '=', 'r.ward_id')->where('r.organization_id', $organizationId)->where('r.is_active', true)->whereNotNull('r.rate_category')->where('r.rate_category', '!=', '')
            ->orderBy('w.name')->orderBy('r.room_number')->limit(100)->get(['w.name as ward', 'r.room_number as room', 'r.rate_category as label', 'r.is_vip as vip'])->map(fn ($x) => ['ward' => (string) $x->ward, 'room' => (string) $x->room, 'label' => (string) $x->label, 'vip' => (bool) $x->vip])->all();
    }
}
