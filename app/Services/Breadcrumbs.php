<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * The trail above a page title: Dashboard > section > menu group > menu entry > this page.
 * It is read from the sidebar menu (config/adminlte.php), so a screen that is in the menu is placed
 * the way a person finds it, with nothing to maintain per route.
 */
class Breadcrumbs
{
    /** @var list<array{url: string, text: string, section: ?string, group: ?string}>|null */
    private ?array $index = null;

    public function __construct(protected Request $request) {}

    /**
     * @return list<array{text: string, url?: string|null}>
     */
    public function generate(?string $title = null): array
    {
        $path = '/'.trim($this->request->path(), '/');
        if ($path === '/dashboard' || $path === '/') {
            return [['text' => __('Dashboard'), 'url' => null]];
        }

        $trail = [['text' => __('Dashboard'), 'url' => url('/dashboard')]];
        $entry = $this->match($path);

        if ($entry !== null) {
            foreach ([$entry['section'], $entry['group']] as $label) {
                if ($label !== null && $label !== $entry['text']) {
                    $trail[] = ['text' => __($label), 'url' => null];
                }
            }
            $exact = rtrim($entry['url'], '/') === rtrim($path, '/');
            $trail[] = ['text' => __($entry['text']), 'url' => $exact ? null : url($entry['url'])];
            if (! $exact && $title !== null && $title !== '' && $title !== $entry['text']) {
                $trail[] = ['text' => $title, 'url' => null];
            }
        } elseif ($title !== null && $title !== '') {
            $trail[] = ['text' => $title, 'url' => null];
        }

        return $trail;
    }

    /** @return array{url: string, text: string, section: ?string, group: ?string}|null the menu entry that owns this path (exact, else the longest parent address) */
    private function match(string $path): ?array
    {
        $best = null;
        foreach ($this->index() as $entry) {
            $url = rtrim($entry['url'], '/');
            if ($url === '' || $url === '/dashboard') {
                continue;
            }
            if ($path === $url || str_starts_with($path, $url.'/')) {
                if ($best === null || strlen($url) > strlen($best['url'])) {
                    $best = $entry;
                }
            }
        }

        return $best;
    }

    /** @return list<array{url: string, text: string, section: ?string, group: ?string}> */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $out = [];
        $section = null;
        foreach ((array) config('adminlte.menu', []) as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (isset($item['header'])) {
                $section = (string) $item['header'];

                continue;
            }
            $this->walk($item, $section, null, $out);
        }

        return $this->index = $out;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<array{url: string, text: string, section: ?string, group: ?string}>  $out
     */
    private function walk(array $item, ?string $section, ?string $group, array &$out): void
    {
        $text = (string) ($item['text'] ?? '');
        if (isset($item['url']) && is_string($item['url']) && $text !== '') {
            $out[] = ['url' => $item['url'], 'text' => $text, 'section' => $section, 'group' => $group];
        }
        foreach ((array) ($item['submenu'] ?? []) as $child) {
            if (is_array($child)) {
                $this->walk($child, $section, $text !== '' ? $text : $group, $out);
            }
        }
    }

    public function render(?string $title = null): string
    {
        $crumbs = $this->generate($title);
        $last = count($crumbs) - 1;
        $html = '';

        foreach ($crumbs as $i => $crumb) {
            $url = $crumb['url'] ?? null;
            if ($i === $last) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">'.e($crumb['text']).'</li>';
            } elseif ($url !== null) {
                $html .= '<li class="breadcrumb-item"><a href="'.e($url).'">'.e($crumb['text']).'</a></li>';
            } else {
                $html .= '<li class="breadcrumb-item"><span class="text-muted">'.e($crumb['text']).'</span></li>';
            }
        }

        return $html;
    }
}
