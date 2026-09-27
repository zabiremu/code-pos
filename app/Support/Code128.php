<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Code 128 barcode as an inline SVG - no packages needed. Uses code set B
 * (all printable ASCII), switching to set C for long digit runs so numeric
 * SKUs print shorter. Any handheld scanner reads it as typed keystrokes +
 * Enter, which is exactly what the register's search box expects.
 */
class Code128
{
    /** Bar/space module widths for symbols 0-106 (106 = stop). */
    private const PATTERNS = [
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
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    private const START_B = 104;
    private const START_C = 105;
    private const CODE_B = 100;
    private const CODE_C = 99;
    private const STOP = 106;

    /** @return int[] symbol values including start, checksum and stop */
    public static function encode(string $text): array
    {
        if ($text === '' || preg_match('/[^\x20-\x7E]/', $text)) {
            throw new InvalidArgumentException('Code 128 needs printable ASCII text.');
        }

        $symbols = [];
        $set = null;
        $i = 0;
        $len = strlen($text);

        while ($i < $len) {
            // Use set C for runs of 4+ digits (or 2+ at the very start/end of an all-digit tail).
            $run = strspn($text, '0123456789', $i);
            $useC = $run >= 4 || ($run >= 2 && $run === $len - $i && $set === null);
            if ($useC) {
                $run -= $run % 2; // set C takes pairs
            }

            if ($useC && $run >= 2) {
                $symbols[] = $set === null ? self::START_C : ($set === 'C' ? null : self::CODE_C);
                $set = 'C';
                for ($j = 0; $j < $run; $j += 2) {
                    $symbols[] = (int) substr($text, $i + $j, 2);
                }
                $i += $run;
            } else {
                $symbols[] = $set === null ? self::START_B : ($set === 'B' ? null : self::CODE_B);
                $set = 'B';
                $symbols[] = ord($text[$i]) - 32;
                $i++;
            }
        }

        $symbols = array_values(array_filter($symbols, fn ($s) => $s !== null));

        $checksum = $symbols[0];
        for ($k = 1, $n = count($symbols); $k < $n; $k++) {
            $checksum += $symbols[$k] * $k;
        }
        $symbols[] = $checksum % 103;
        $symbols[] = self::STOP;

        return $symbols;
    }

    /** Module widths, bar first, alternating bar/space. */
    public static function modules(string $text): string
    {
        return implode('', array_map(fn ($s) => self::PATTERNS[$s], self::encode($text)));
    }

    /**
     * @param  float  $height  in SVG units; the SVG scales to its container width
     */
    public static function svg(string $text, float $height = 40, int $quiet = 10): string
    {
        $modules = self::modules($text);
        $x = $quiet;
        $bars = '';
        foreach (str_split($modules) as $k => $w) {
            $w = (int) $w;
            if ($k % 2 === 0) {
                $bars .= '<rect x="'.$x.'" y="0" width="'.$w.'" height="'.$height.'"/>';
            }
            $x += $w;
        }
        $total = $x + $quiet;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$total.' '.$height.'" preserveAspectRatio="none" role="img" aria-label="Barcode '.e($text).'" shape-rendering="crispEdges" fill="#000">'.$bars.'</svg>';
    }
}
