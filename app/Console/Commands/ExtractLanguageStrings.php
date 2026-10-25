<?php

namespace App\Console\Commands;

use App\Services\Localization\BladeTextExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExtractLanguageStrings extends Command
{
    protected $signature = 'lang:extract {--apply : Rewrite the views (default: report only)} {--path=resources/views : Directory to scan} {--skipped : Also list text that needs manual wrapping}';

    protected $description = 'Wrap plain English text in Blade views with __() and add the new keys to lang/bn.json';

    public function handle(BladeTextExtractor $extractor): int
    {
        $keys = [];
        $skipped = [];
        $changed = 0;

        foreach (File::allFiles(base_path($this->option('path'))) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php') || str_contains($file->getPathname(), '/vendor/')) {
                continue;
            }

            $result = $extractor->process(File::get($file->getPathname()));
            $keys = array_merge($keys, $result['keys']);
            $skipped = array_merge($skipped, array_map(fn ($s) => $file->getRelativePathname().': '.$s, $result['skipped']));

            if ($result['keys'] !== [] && $this->option('apply')) {
                File::put($file->getPathname(), $result['content']);
                $changed++;
            }
        }

        $unique = array_values(array_unique($keys));
        $this->info(count($keys).' strings found ('.count($unique).' unique); '.count($skipped).' fragments left for manual wrapping.');

        if ($this->option('skipped')) {
            foreach ($skipped as $line) {
                $this->line($line);
            }
        }

        if ($this->option('apply')) {
            $path = lang_path('bn.json');
            $existing = File::exists($path) ? json_decode(File::get($path), true) : [];
            foreach ($unique as $key) {
                $existing[$key] ??= '';   // empty = not translated yet; the English text is shown meanwhile
            }
            ksort($existing);
            File::put($path, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
            $this->info("Rewrote $changed view(s); lang/bn.json now has ".count($existing).' keys.');
        }

        return self::SUCCESS;
    }
}
