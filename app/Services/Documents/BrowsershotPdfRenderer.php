<?php

namespace App\Services\Documents;

use App\Contracts\Documents\PdfRenderer;
use Spatie\Browsershot\Browsershot;

/**
 * Prints the document HTML to PDF with headless Chromium, so Bangla conjuncts (ক্ষ, জ্ঞ, শ্রী…) are shaped exactly as on screen
 * (guideline 14.6, decision OD "Chromium"). Chromium and the `puppeteer` npm package must be present on the server.
 */
class BrowsershotPdfRenderer implements PdfRenderer
{
    private const PAPER = [
        'thermal80' => [80, 200],   // 80 mm roll, cut at 200 mm
        'label' => [90, 30],
    ];

    public function render(string $html, string $paper): string
    {
        $shot = Browsershot::html($html)->margins(4, 4, 4, 4, 'mm')->showBackground();

        if ($path = config('documents.pdf.chrome_path')) {
            $shot->setChromePath($path);
        }
        if ($node = config('documents.pdf.node_binary')) {
            $shot->setNodeBinary($node);
        }
        if ($npm = config('documents.pdf.npm_binary')) {
            $shot->setNpmBinary($npm);
        }
        config('documents.pdf.no_sandbox') && $shot->noSandbox();

        if (isset(self::PAPER[$paper])) {
            [$width, $height] = self::PAPER[$paper];
            $shot->paperSize($width, $height, 'mm');
        } else {
            $shot->format(strtoupper($paper) === 'A5' ? 'A5' : 'A4');
        }

        return $shot->pdf();
    }
}
