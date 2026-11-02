<?php

namespace Tests\Feature\Dashboard;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\DashboardCache;
use App\Services\Dashboard\Targets;
use App\Services\SettingsService;
use App\Support\AnalyticsFormat;
use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Kpi;
use App\Support\Dashboard\Numbers;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/** The shared parts every dashboard is built from: how figures are written, the arithmetic of change and target, the period, the cache, and the components. */
class FoundationTest extends TestCase
{
    use RefreshDatabase;

    // ---- numbers ------------------------------------------------------------------------------------------------------------------------------

    public function test_figures_are_grouped_the_international_way_unless_the_hospital_chooses_otherwise(): void
    {
        $this->assertSame('1,234,567', Numbers::number(1234567));
        $this->assertSame('1,234.50', Numbers::number(1234.5, 2));
        $this->assertSame('-9,876', Numbers::number(-9876));

        app(SettingsService::class)->set('ux.number_grouping', 'south_asian');
        $this->assertSame('12,34,567', Numbers::number(1234567));
        $this->assertSame('1,00,00,000', Numbers::number(10000000), 'one crore');
        $this->assertSame('1,23,456.75', Numbers::number(123456.75, 2));
        $this->assertSame('999', Numbers::number(999));
        $this->assertSame('1,000', Numbers::number(1000));
        $this->assertSame('-12,345', Numbers::number(-12345));
        $this->assertSame('৳ 12,34,567', Numbers::value(1234567, 'taka'));
    }

    public function test_bangla_digits_only_when_the_interface_is_bangla_and_the_hospital_asks(): void
    {
        $this->assertSame('1,234', Numbers::number(1234));
        app(SettingsService::class)->set('ux.bangla_digits', true);
        $this->assertSame('1,234', Numbers::number(1234), 'English interface keeps English digits');
        app()->setLocale('bn');
        $this->assertSame('১,২৩৪', Numbers::number(1234));
        $this->assertSame('১২.৫%', Numbers::value(12.5, 'percent'));
        $this->assertSame('—', Numbers::value(null, 'count'));
        app()->setLocale('en');
    }

    public function test_every_unit_is_written_with_its_word_or_sign(): void
    {
        $this->assertSame('45.5%', Numbers::value(45.5, 'percent'));
        $this->assertSame('12 min', Numbers::value(12, 'minutes'));
        $this->assertSame('2.5 h', Numbers::value(2.5, 'hours'));
        $this->assertSame('3.0 days', Numbers::value(3, 'days'));
        $this->assertSame('0.25', Numbers::value(0.25, 'ratio'));
        $this->assertSame('1,200', AnalyticsFormat::value(1200, 'count'), 'the BI screens use the same writer');
    }

    // ---- kpi ----------------------------------------------------------------------------------------------------------------------------------

    public function test_change_is_against_the_period_before_and_silent_when_there_is_nothing_to_compare(): void
    {
        $this->assertSame(25.0, Kpi::change(125, 100));
        $this->assertSame(-20.0, Kpi::change(80, 100));
        $this->assertSame(0.0, Kpi::change(100, 100));
        $this->assertNull(Kpi::change(100, 0), 'a rise from zero has no percentage');
        $this->assertNull(Kpi::change(100, null));
        $this->assertNull(Kpi::change(null, 100));
        $this->assertTrue(Kpi::changeIsGood(10.0, 'up'));
        $this->assertFalse(Kpi::changeIsGood(10.0, 'down'));
        $this->assertTrue(Kpi::changeIsGood(-10.0, 'down'));
        $this->assertNull(Kpi::changeIsGood(10.0, 'none'));
        $this->assertNull(Kpi::changeIsGood(0.0, 'up'));
    }

    public function test_a_target_gives_good_close_or_off_with_an_icon_and_a_word_and_none_without_a_target(): void
    {
        // up is good: target 90, "close" is within 10% of 90 = 9 points
        $this->assertSame('good', Kpi::status(90, 90, 'up')['state']);
        $this->assertSame('good', Kpi::status(95, 90, 'up')['state']);
        $this->assertSame('warn', Kpi::status(85, 90, 'up')['state']);
        $this->assertSame('warn', Kpi::status(81, 90, 'up')['state']);
        $this->assertSame('bad', Kpi::status(80, 90, 'up')['state']);
        // down is good (minutes to see a doctor): target 15
        $this->assertSame('good', Kpi::status(14, 15, 'down')['state']);
        $this->assertSame('warn', Kpi::status(16, 15, 'down')['state']);
        $this->assertSame('bad', Kpi::status(20, 15, 'down')['state']);
        foreach ([Kpi::status(90, 90, 'up'), Kpi::status(85, 90, 'up'), Kpi::status(1, 90, 'up')] as $s) {
            $this->assertNotSame('', $s['icon'], 'never colour alone');
            $this->assertNotSame('', $s['word'], 'never colour alone');
        }
        $this->assertSame('neutral', Kpi::status(50, null, 'up')['state']);
        $this->assertSame('neutral', Kpi::status(50, 90, 'none')['state']);
        $this->assertSame('neutral', Kpi::status(null, 90, 'up')['state']);
    }

    public function test_targets_belong_to_the_hospital_and_none_ships(): void
    {
        $t = app(Targets::class);
        $this->assertNull($t->get('ed_door_to_doctor'));
        app(SettingsService::class)->set('dashboard.target.ed_door_to_doctor', '15');
        $this->assertSame(15.0, $t->get('ed_door_to_doctor'));
        app(SettingsService::class)->set('dashboard.target.bad', 'abc');
        $this->assertNull($t->get('bad'));
    }

    // ---- period -------------------------------------------------------------------------------------------------------------------------------

    private function at(string $key, ?string $from = null, ?string $to = null): DashboardPeriod
    {
        return DashboardPeriod::make($key, $from, $to, null, null, CarbonImmutable::parse('2026-11-12 10:00:00', 'Asia/Dhaka'));
    }

    public function test_each_period_starts_and_ends_on_whole_dhaka_days(): void
    {
        $p = $this->at('today');
        $this->assertSame(['2026-11-12', '2026-11-12', 1], [$p->fromDate(), $p->toDate(), $p->days()]);
        $this->assertSame('2026-11-12 00:00:00', $p->from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-12 23:59:59', $p->to->format('Y-m-d H:i:s'));
        $this->assertSame(['2026-11-06', '2026-11-12', 7], [$this->at('7d')->fromDate(), $this->at('7d')->toDate(), $this->at('7d')->days()]);
        $this->assertSame(['2026-10-14', 30], [$this->at('30d')->fromDate(), $this->at('30d')->days()]);
        $this->assertSame(['2026-08-15', 90], [$this->at('90d')->fromDate(), $this->at('90d')->days()]);
        $this->assertSame(['2026-11-01', '2026-11-12'], [$this->at('month')->fromDate(), $this->at('month')->toDate()]);
        $this->assertSame(['2026-10-01', '2026-10-31'], [$this->at('last_month')->fromDate(), $this->at('last_month')->toDate()]);
    }

    public function test_queries_on_utc_columns_use_the_dhaka_day_boundaries(): void
    {
        $p = $this->at('today');
        $this->assertSame('2026-11-11 18:00:00', $p->fromUtc()->format('Y-m-d H:i:s'), 'Dhaka midnight is 18:00 UTC the evening before');
        $this->assertSame('2026-11-12 17:59:59', $p->toUtc()->format('Y-m-d H:i:s'));
    }

    public function test_the_period_before_is_as_long_and_straight_before(): void
    {
        $p = $this->at('7d')->previous();
        $this->assertSame(['2026-10-30', '2026-11-05', 7], [$p->fromDate(), $p->toDate(), $p->days()]);
        $m = $this->at('month')->previous();
        $this->assertSame(['2026-10-01', '2026-10-31'], [$m->fromDate(), $m->toDate()], 'the month so far is compared with the whole month before');
        $l = $this->at('last_month')->previous();
        $this->assertSame(['2026-09-01', '2026-09-30'], [$l->fromDate(), $l->toDate()]);
    }

    public function test_a_custom_range_is_checked_and_a_bad_one_falls_back_to_the_default(): void
    {
        $ok = $this->at('custom', '2026-10-01', '2026-10-10');
        $this->assertSame(['custom', 10, ['period' => 'custom', 'from' => '2026-10-01', 'to' => '2026-10-10']], [$ok->key, $ok->days(), $ok->query()]);
        foreach ([['2026-10-10', '2026-10-01'], ['2026-10-01', '2026-12-01'], ['2024-01-01', '2026-11-01'], ['nonsense', 'x'], [null, null]] as [$f, $t]) {
            $this->assertSame('30d', $this->at('custom', $f, $t)->key, json_encode([$f, $t]));
        }
        $this->assertSame('30d', $this->at('bogus')->key);
        $this->assertSame('7d', DashboardPeriod::make('90d', null, null, '7d', ['7d', '30d'])->key, 'a period the dashboard does not offer falls back to its own default');
    }

    // ---- cache --------------------------------------------------------------------------------------------------------------------------------

    private function user(array $permissions): User
    {
        $u = User::factory()->create();
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
            $u->givePermissionTo($p);
        }

        return $u;
    }

    public function test_the_cache_never_lets_one_scope_see_another_scopes_figures(): void
    {
        Cache::flush();
        $cache = app(DashboardCache::class);
        $period = $this->at('7d');
        $a = $this->user(['x.view']);
        $b = $this->user(['x.view', 'y.view']);
        $calls = 0;
        $compute = function () use (&$calls) {
            $calls++;

            return ['n' => $calls];
        };

        $first = $cache->remember($a, 'demo', 1, 1, $period, $compute);
        $again = $cache->remember($a, 'demo', 1, 1, $period, $compute);
        $this->assertSame([1, 1], [$first['data']['n'], $again['data']['n']], 'same scope: worked out once');
        $this->assertEquals($first['as_of'], $again['as_of'], 'the time it was worked out is kept');
        $this->assertSame(2, $cache->remember($b, 'demo', 1, 1, $period, $compute)['data']['n'], 'other rights: worked out again');
        $this->assertSame(3, $cache->remember($a, 'demo', 2, 1, $period, $compute)['data']['n'], 'other hospital');
        $this->assertSame(4, $cache->remember($a, 'demo', 1, 2, $period, $compute)['data']['n'], 'other branch');
        $this->assertSame(5, $cache->remember($a, 'demo', 1, 1, $this->at('30d'), $compute)['data']['n'], 'other period');
        $this->assertSame(6, $cache->remember($a, 'demo', 1, 1, $period, $compute, fresh: true)['data']['n'], 'refresh works it out now');
        $this->assertSame(6, $cache->remember($a, 'demo', 1, 1, $period, $compute)['data']['n'], 'and the new figure is kept');
        $this->assertSame(7, $cache->remember($a, 'demo', 1, 1, $period, $compute, 0)['data']['n'], 'no cache when the hospital turns it off');
        $this->assertSame(8, $cache->remember($a, 'demo', 1, 1, $period, $compute, 0)['data']['n']);
    }

    // ---- components ---------------------------------------------------------------------------------------------------------------------------

    public function test_the_plain_tile_is_unchanged_for_the_screens_that_already_use_it(): void
    {
        $html = Blade::render('<x-ui.kpi-tile label="Open incidents" :value="7" icon="bi-exclamation-triangle" variant="warning" href="/x" />');
        $this->assertStringContainsString('Open incidents', $html);
        $this->assertStringContainsString('>7<', preg_replace('/\s+/', '', str_replace('>7<', '>7<', $html)) ? $html : '');
        $this->assertStringContainsString('href="/x"', $html);
        $this->assertStringNotContainsString('vs the period before', $html);
        $this->assertStringNotContainsString('Target', $html);
    }

    public function test_a_tile_with_a_unit_shows_the_change_the_target_and_a_trend(): void
    {
        app(SettingsService::class)->set('ux.number_grouping', 'south_asian');
        $html = Blade::render('<x-ui.kpi-tile label="Given" :value="1250000" :previous="1000000" unit="taka" :target="1500000" :spark="[1,3,2,5]" href="/list" />');
        $this->assertStringContainsString('৳ 12,50,000', $html);
        $this->assertStringContainsString('▲', $html);
        $this->assertStringContainsString('+25%', $html);
        $this->assertStringContainsString('vs the period before', $html);
        $this->assertStringContainsString('Off target', $html, 'a word, not only a colour');
        $this->assertStringContainsString('bi-x-circle', $html, 'and an icon');
        $this->assertStringContainsString('<svg', $html, 'the trend');
        $this->assertStringContainsString('href="/list"', $html);

        $good = Blade::render('<x-ui.kpi-tile label="Minutes" :value="12" :previous="20" unit="minutes" :target="15" good-direction="down" />');
        $this->assertStringContainsString('On target', $good);
        $this->assertStringContainsString('text-success', $good, 'a fall is good when lower is better');
    }

    public function test_a_hidden_small_number_is_said_not_shown(): void
    {
        $html = Blade::render('<x-ui.kpi-tile label="Cases" :value="3" unit="count" :suppressed="true" />');
        $this->assertStringContainsString('Hidden (small number)', $html);
        $this->assertStringNotContainsString('>3<', $html);
    }

    public function test_every_chart_has_a_title_a_table_and_series_that_differ_by_more_than_colour(): void
    {
        $rows = [['m' => 'Jan', 'a' => 10, 'b' => 4], ['m' => 'Feb', 'a' => 20, 'b' => 8]];
        $series = [['key' => 'a', 'label' => 'Received'], ['key' => 'b', 'label' => 'Given']];
        $bar = Blade::render('<x-ui.chart kind="bar" :rows="$rows" x="m" :series="$series" />', compact('rows', 'series'));
        $this->assertStringContainsString('role="img"', $bar);
        $this->assertStringContainsString('<title>Received, Given</title>', $bar);
        $this->assertStringContainsString('Show as a table', $bar);
        $this->assertStringContainsString('Received', $bar);
        $line = Blade::render('<x-ui.chart kind="line" :rows="$rows" x="m" :series="$series" />', compact('rows', 'series'));
        $this->assertStringContainsString('stroke-dasharray', $line, 'the second line is dashed');
        $h = Blade::render('<x-ui.chart kind="hbar" :rows="$rows" x="m" :series="[$series[0]]" unit="taka" />', compact('rows', 'series'));
        $this->assertStringContainsString('৳ 20', $h);
        $spark = Blade::render('<x-ui.chart kind="spark" :rows="$rows" x="m" :series="[$series[0]]" />', compact('rows', 'series'));
        $this->assertStringContainsString('Trend', $spark);
        $this->assertStringNotContainsString('Show as a table', $spark);
        $empty = Blade::render('<x-ui.chart kind="bar" :rows="[]" x="m" :series="$series" />', compact('series'));
        $this->assertStringContainsString('Nothing to show for this period.', $empty);
    }

    public function test_the_dashboard_frame_states_the_question_the_period_the_time_and_when_it_reloads(): void
    {
        $period = $this->at('7d');
        $html = Blade::render('<x-ui.dashboard question="Are we on time?" audience="For the ward manager" :period="$period" :as-of="$asOf" :refresh="60">BODY</x-ui.dashboard>',
            ['period' => $period, 'asOf' => CarbonImmutable::parse('2026-11-12 08:30:00', 'UTC')]);
        $this->assertStringContainsString('Are we on time?', $html);
        $this->assertStringContainsString('For the ward manager', $html);
        $this->assertStringContainsString('name="period"', $html);
        $this->assertStringContainsString('Last 7 days', $html);
        $this->assertStringContainsString('As of', $html);
        $this->assertStringContainsString('14:30', $html, 'shown in Dhaka time (UTC+6)');
        $this->assertStringContainsString('Reloads every 60 seconds', $html);
        $this->assertStringContainsString('BODY', $html);
        $this->assertStringContainsString('fresh=1', $html, 'the refresh button asks for a fresh figure');

        $quiet = Blade::render('<x-ui.dashboard question="Q">BODY</x-ui.dashboard>');
        $this->assertStringNotContainsString('Reloads every', $quiet);
        $this->assertStringNotContainsString('name="period"', $quiet);
        $this->assertStringNotContainsString('As of', $quiet);
    }

    public function test_the_other_hospitals_gets_nothing_from_a_cache_key(): void
    {
        $o = Organization::factory()->create();
        $this->assertNotSame(
            app(DashboardCache::class)->key($this->user(['a']), 'd', $o->id, null, null),
            app(DashboardCache::class)->key($this->user(['a']), 'd', $o->id + 1, null, null),
        );
    }
}
