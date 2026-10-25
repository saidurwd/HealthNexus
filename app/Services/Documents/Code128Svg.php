<?php

namespace App\Services\Documents;

use InvalidArgumentException;

/**
 * Code 128 (subset B, printable ASCII 32-126) as inline SVG, for wristbands, specimen labels and receipts.
 * Bar/space widths per the published Code 128 symbol table (value 0-105, then STOP).
 */
class Code128Svg
{
    private const WIDTHS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232',
    ];

    private const STOP = '2331112';

    private const START_B = 104;

    /** @return list<int> symbol values including start, data, checksum (stop excluded) */
    public function symbols(string $text): array
    {
        if ($text === '') {
            throw new InvalidArgumentException('Nothing to encode.');
        }

        $values = [self::START_B];
        foreach (str_split($text) as $i => $char) {
            $code = ord($char);
            if ($code < 32 || $code > 126) {
                throw new InvalidArgumentException('Code 128-B encodes printable ASCII only.');
            }
            $values[] = $code - 32;
        }

        $sum = self::START_B;
        foreach (array_slice($values, 1) as $i => $value) {
            $sum += $value * ($i + 1);
        }
        $values[] = $sum % 103;

        return $values;
    }

    public function render(string $text, int $moduleWidth = 2, int $height = 50, bool $showText = true): string
    {
        $patterns = array_map(fn (int $v) => self::WIDTHS[$v], $this->symbols($text));
        $patterns[] = self::STOP;

        $x = 10; // quiet zone
        $bars = '';
        foreach ($patterns as $pattern) {
            foreach (str_split($pattern) as $i => $width) {
                $w = (int) $width * $moduleWidth;
                if ($i % 2 === 0) {
                    $bars .= sprintf('<rect x="%d" y="0" width="%d" height="%d"/>', $x, $w, $height);
                }
                $x += $w;
            }
        }

        $total = $x + 10;
        $label = $showText ? sprintf('<text x="%d" y="%d" font-size="12" text-anchor="middle" font-family="monospace">%s</text>', $total / 2, $height + 14, htmlspecialchars($text, ENT_QUOTES)) : '';

        return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" role="img" aria-label="Barcode %s"><g fill="#000">%s</g>%s</svg>',
            $total, $height + ($showText ? 18 : 0), $total, $height + ($showText ? 18 : 0), htmlspecialchars($text, ENT_QUOTES), $bars, $label);
    }
}
