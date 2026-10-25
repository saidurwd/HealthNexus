<?php

namespace Tests\Feature\Localization;

use App\Services\NotificationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CI gate (guideline 19.4, H-10): every user-visible string that the code asks to translate must have a Bangla translation,
 * every PHP language file must exist in both languages with the same keys, and placeholders must survive translation.
 */
class BanglaParityTest extends TestCase
{
    private function bangla(): array
    {
        return json_decode(File::get(lang_path('bn.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<string, list<string>> key => files that use it */
    private function usedKeys(): array
    {
        $used = [];
        $groups = collect(File::files(lang_path('en')))->map(fn ($f) => $f->getFilenameWithoutExtension())->all();
        $roots = [resource_path('views'), app_path(), base_path('modules')];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }
            foreach (File::allFiles($root) as $file) {
                if (! preg_match('/\.php$/', $file->getFilename()) || str_contains($file->getPathname(), '/vendor/')) {
                    continue;
                }
                preg_match_all("/(?:__|trans)\('((?:[^'\\\\]|\\\\.)*)'/", File::get($file->getPathname()), $m);
                foreach ($m[1] as $raw) {
                    $key = str_replace("\\'", "'", $raw);
                    $group = explode('.', $key)[0];
                    if (str_contains($key, '.') && in_array($group, $groups, true)) {
                        continue; // group.key lookups are checked by the PHP language file test below
                    }
                    if (! preg_match('/[A-Za-z]{2,}/', $key) || str_contains($key, '$')) {
                        continue;
                    }
                    $used[$key][] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        return $used;
    }

    public function test_every_translatable_string_in_the_code_has_a_bangla_translation(): void
    {
        $bangla = $this->bangla();
        $missing = [];

        foreach ($this->usedKeys() as $key => $files) {
            if (($bangla[$key] ?? '') === '') {
                $missing[] = $key.'   ('.$files[0].')';
            }
        }

        $this->assertSame([], array_slice($missing, 0, 200), count($missing).' string(s) have no Bangla translation in lang/bn.json (first 200 shown). Add them, then run this test again.');
    }

    public function test_menu_and_notification_texts_are_translated(): void
    {
        $bangla = $this->bangla();
        $texts = [];

        $walk = function (array $items) use (&$walk, &$texts) {
            foreach ($items as $item) {
                foreach (['text', 'header', 'label'] as $field) {
                    if (! empty($item[$field]) && is_string($item[$field])) {
                        $texts[] = $item[$field];
                    }
                }
                $walk($item['submenu'] ?? []);
            }
        };
        $walk(config('adminlte.menu'));

        foreach (app(NotificationService::class)->templates() as $template) {
            array_push($texts, $template['title'], $template['message']);
        }

        $missing = array_values(array_filter(array_unique($texts), fn ($t) => $t !== '' && ($bangla[$t] ?? '') === ''));

        $this->assertSame([], $missing);
    }

    public function test_translations_keep_every_placeholder_of_the_english_text(): void
    {
        $broken = [];

        foreach ($this->bangla() as $english => $translation) {
            preg_match_all('/:[a-z_]+/', $english, $placeholders);
            foreach ($placeholders[0] as $placeholder) {
                if ($translation !== '' && ! str_contains($translation, $placeholder)) {
                    $broken[] = "$english → missing $placeholder";
                }
            }
        }

        $this->assertSame([], $broken);
    }

    public function test_php_language_files_exist_in_english_and_bangla_with_the_same_keys(): void
    {
        $english = collect(File::files(lang_path('en')))->map(fn ($f) => $f->getFilename())->sort()->values()->all();
        $bangla = collect(File::files(lang_path('bn')))->map(fn ($f) => $f->getFilename())->sort()->values()->all();

        // Bangla may add overrides of Laravel's built-in groups (validation, auth, passwords); every app file must exist in both.
        $appFiles = array_values(array_filter($english, fn ($f) => ! in_array($f, ['validation.php', 'auth.php', 'passwords.php'], true)));
        $this->assertSame($appFiles, array_values(array_diff($bangla, ['validation.php', 'auth.php', 'passwords.php'])), 'lang/en and lang/bn must contain the same files');

        foreach ($english as $file) {
            $en = $this->flatten(require lang_path("en/$file"));
            $bn = $this->flatten(require lang_path("bn/$file"));
            sort($en);
            sort($bn);
            $this->assertSame($en, $bn, "lang/en/$file and lang/bn/$file differ in keys");
        }
    }

    public function test_every_status_in_the_ux_palette_has_a_bangla_label(): void
    {
        $bangla = $this->bangla();
        $missing = [];

        foreach (array_keys(config('ux.statuses')) as $status) {
            $label = Str::headline($status);
            if (($bangla[$label] ?? '') === '') {
                $missing[] = $label;
            }
        }

        $this->assertSame([], $missing);
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $out = [];
        foreach ($array as $key => $value) {
            $out = array_merge($out, is_array($value) ? $this->flatten($value, "$prefix$key.") : ["$prefix$key"]);
        }

        return $out;
    }

    public function test_bangla_file_is_valid_utf8_json_without_blank_values(): void
    {
        $blank = array_keys(array_filter($this->bangla(), fn ($v) => $v === ''));

        $this->assertSame([], array_slice($blank, 0, 20), count($blank).' key(s) are still untranslated.');
    }
}
