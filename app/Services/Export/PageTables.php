<?php

namespace App\Services\Export;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Reads the tables a staff screen shows, from its rendered HTML, so every list and report can be exported
 * without each screen having to say how. Buttons, forms and empty-state rows are left out; a column that is empty in every row
 * (an actions column) is dropped.
 *
 * @phpstan-type Table array{name: string, header: list<string>, rows: list<list<string>>}
 */
class PageTables
{
    /** @return array{title: string, tables: list<Table>, pages: int} */
    public function read(string $html): array
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xp = new DOMXPath($dom);

        $title = $this->text($xp->query('//*[@id="page-title"]')->item(0)) ?: $this->text($xp->query('//title')->item(0));
        $tables = [];
        $seen = [];

        foreach ($xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " app-content ")]//table') ?: [] as $node) {
            if (! $node instanceof DOMElement || $this->inside($node, ['modal', 'd-none', 'collapse', 'dropdown-menu'])) {
                continue;
            }
            $table = $this->table($node, $xp);
            if ($table === null) {
                continue;
            }
            $base = $table['name'];
            $n = $seen[$base] = ($seen[$base] ?? 0) + 1;
            $table['name'] = $n > 1 ? $base.' '.$n : $base;
            $tables[] = $table;
        }

        return ['title' => $title, 'tables' => $tables, 'pages' => $this->lastPage($xp)];
    }

    /** The highest "page=" number the screen's page links point to. */
    private function lastPage(DOMXPath $xp): int
    {
        $last = 1;
        foreach ($xp->query('//ul[contains(@class,"pagination")]//a/@href') ?: [] as $href) {
            if (preg_match('/[?&]page=(\d+)/', $href->nodeValue ?? '', $m)) {
                $last = max($last, (int) $m[1]);
            }
        }

        return $last;
    }

    /** @return Table|null */
    private function table(DOMElement $node, DOMXPath $xp): ?array
    {
        $header = [];
        $rows = [];

        foreach ($xp->query('.//tr', $node) ?: [] as $tr) {
            if (! $tr instanceof DOMElement || $this->nearestTable($tr) !== $node) {
                continue;
            }
            $cells = [];
            $isHead = $tr->parentNode instanceof DOMElement && $tr->parentNode->nodeName === 'thead';
            $emptyNote = false;
            foreach ($xp->query('./th|./td', $tr) ?: [] as $cell) {
                if (! $cell instanceof DOMElement) {
                    continue;
                }
                $span = max(1, (int) $cell->getAttribute('colspan'));
                if ($span > 1 && ! $isHead && $tr->getElementsByTagName('td')->length + $tr->getElementsByTagName('th')->length === 1) {
                    $emptyNote = true; // "Nothing found" rows say nothing
                }
                $cells[] = $this->text($cell);
                for ($i = 1; $i < $span; $i++) {
                    $cells[] = '';
                }
            }
            if ($emptyNote || $cells === []) {
                continue;
            }
            if ($isHead) {
                $header = $cells;
            } else {
                $rows[] = $cells;
            }
        }

        if ($rows === []) {
            return null;
        }

        $width = max(count($header), ...array_map('count', $rows));
        $header = array_pad($header, $width, '');
        $rows = array_map(fn ($r) => array_pad($r, $width, ''), $rows);

        // Drop columns that are empty in every row (button columns, check boxes).
        $keep = [];
        for ($c = 0; $c < $width; $c++) {
            if (array_filter(array_column($rows, $c), fn ($v) => $v !== '') !== []) {
                $keep[] = $c;
            }
        }
        if ($keep === []) {
            return null;
        }
        $header = array_map(fn ($c) => $header[$c], $keep);
        $rows = array_map(fn ($r) => array_map(fn ($c) => $r[$c], $keep), $rows);
        if (array_filter($header, fn ($v) => $v !== '') === []) {
            $header = [];
        }

        return ['name' => $this->heading($node, $xp) ?: __('Table'), 'header' => $header, 'rows' => $rows];
    }

    private function heading(DOMElement $table, DOMXPath $xp): string
    {
        for ($n = $table->parentNode; $n instanceof DOMElement; $n = $n->parentNode) {
            if ($this->hasClass($n, 'card')) {
                $h = $xp->query('.//*[contains(concat(" ", normalize-space(@class), " "), " card-title ")]|.//h1|.//h2|.//h3|.//h4|.//h5', $n)->item(0);
                $text = $this->text($h);
                if ($text !== '') {
                    return $text;
                }
            }
        }
        $h = $xp->query('preceding::*[self::h2 or self::h3 or self::h4 or self::h5][1]', $table)->item(0);

        return $this->text($h);
    }

    private function nearestTable(DOMNode $node): ?DOMNode
    {
        for ($n = $node->parentNode; $n !== null; $n = $n->parentNode) {
            if ($n->nodeName === 'table') {
                return $n;
            }
        }

        return null;
    }

    /** @param  list<string>  $classes */
    private function inside(DOMElement $node, array $classes): bool
    {
        for ($n = $node; $n instanceof DOMElement; $n = $n->parentNode) {
            foreach ($classes as $class) {
                if ($this->hasClass($n, $class)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasClass(DOMElement $node, string $class): bool
    {
        return in_array($class, preg_split('/\s+/', trim($node->getAttribute('class'))) ?: [], true);
    }

    /** The visible words of a node: buttons, forms controls, scripts and hidden parts removed, spaces tidied. */
    private function text(?DOMNode $node): string
    {
        if ($node === null) {
            return '';
        }
        $clone = $node->cloneNode(true);
        $xp = new DOMXPath($node->ownerDocument);
        $drop = $xp->query('.//script|.//style|.//button|.//select|.//textarea|.//input|.//*[contains(@class,"btn")]|.//*[contains(@class,"d-none")]|.//*[contains(@class,"visually-hidden")]|.//*[contains(@class,"dropdown-menu")]', $clone) ?: [];
        foreach (iterator_to_array($drop) as $d) {
            $d->parentNode?->removeChild($d);
        }

        return trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $clone->textContent)));
    }
}
