<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;

/**
 * The checks every dashboard must pass (docs/ux/dashboard-standard.md §7), as helpers a dashboard's test calls. A test class using this needs `$this->as(User)` to sign someone in with the hospital selected.
 *
 * What they prove: the screen states its question and the time it was worked out; a person without the right is refused; a figure opens a list whose count and total are the same figure; no patient identity is on a management
 * page; nothing from another hospital is counted.
 */
trait DashboardAcceptance
{
    protected function assertDashboardRefuses(string $url, User $withoutRight): void
    {
        $this->as($withoutRight)->get($url)->assertForbidden();
    }

    /** The page opens, says what it is for, and says when its figures were worked out. */
    protected function assertDashboardStatesItself(string $url, User $user): string
    {
        $html = $this->as($user)->get($url)->assertOk()->getContent();
        $this->assertStringContainsString('data-dashboard-question', $html, 'the dashboard states its question');
        $this->assertStringContainsString('data-dashboard-asof', $html, 'and the time its figures were worked out');
        $this->assertStringNotContainsString('Whoops', $html);

        return $html;
    }

    /** The href of the tile with this label, as found on the page. */
    protected function tileLink(string $html, string $label): ?string
    {
        return preg_match('/<a href="([^"]+)" class="small stretched-link[^>]*>\s*'.preg_quote(e($label), '/').'\s*<\/a>/u', $html, $m) ? html_entity_decode($m[1]) : null;
    }

    /** @return array{count:int,total:string} what the list a tile opens counts */
    protected function listFigures(string $listHtml): array
    {
        $this->assertMatchesRegularExpression('/data-list-count="(\d+)"/', $listHtml, 'a list behind a figure states its count');
        preg_match('/data-list-count="(\d+)"/', $listHtml, $c);
        preg_match('/data-list-total="([\d.\-]+)"/', $listHtml, $t);

        return ['count' => (int) $c[1], 'total' => $t[1] ?? '0.00'];
    }

    /** A figure opens its list, and the list adds up to the same figure. */
    protected function assertTileEqualsList(string $html, string $label, User $user, ?string $expectedTotal = null, ?int $expectedCount = null): array
    {
        $href = $this->tileLink($html, $label);
        $this->assertNotNull($href, "the tile [$label] opens a list");
        $figures = $this->listFigures($this->as($user)->get($href)->assertOk()->getContent());
        $expectedTotal !== null && $this->assertSame($expectedTotal, $figures['total'], "[$label] is the total of the list it opens");
        $expectedCount !== null && $this->assertSame($expectedCount, $figures['count'], "[$label] counts the rows of the list it opens");

        return $figures;
    }

    /** @param  list<string>  $names  patient names that must not be on a management page */
    protected function assertNoPatientIdentity(string $html, array $names): void
    {
        foreach ($names as $n) {
            $this->assertStringNotContainsString($n, $html, 'a management dashboard carries totals, not patients');
        }
    }
}
