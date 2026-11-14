<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Self-contained ISO/IEC 18004 QR Code SVG generator (Byte mode, EC Level L, Versions 1-14).
 * Generates local SVG data URIs for TOTP provisioning URIs without third-party network calls.
 */
final class QrCodeSvg
{
    /**
     * Error Correction Level L table for Versions 1..14:
     * [totalDataCodewords, ecCodewordsPerBlock, numBlocksGroup1, dataCodewordsGroup1, numBlocksGroup2, dataCodewordsGroup2]
     */
    private const VERSION_TABLE_L = [
        1  => [19,  7,  1, 19, 0, 0],
        2  => [34,  10, 1, 34, 0, 0],
        3  => [55,  15, 1, 55, 0, 0],
        4  => [80,  20, 1, 80, 0, 0],
        5  => [108, 26, 1, 108, 0, 0],
        6  => [136, 18, 2, 68, 0, 0],
        7  => [156, 20, 2, 78, 0, 0],
        8  => [194, 24, 2, 97, 0, 0],
        9  => [232, 30, 2, 116, 0, 0],
        10 => [274, 18, 2, 68, 2, 69],
        11 => [324, 20, 4, 81, 0, 0],
        12 => [370, 24, 2, 92, 2, 93],
        13 => [428, 26, 4, 107, 0, 0],
        14 => [461, 30, 3, 115, 1, 116],
    ];

    private const ALIGNMENT_PATTERNS = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
        11 => [6, 30, 54],
        12 => [6, 32, 58],
        13 => [6, 34, 62],
        14 => [6, 26, 46, 66],
    ];

    private const VERSION_INFO_BITS = [
        7  => 0x07C94,
        8  => 0x085BC,
        9  => 0x09A99,
        10 => 0x0A4D3,
        11 => 0x0BBF6,
        12 => 0x0C762,
        13 => 0x0D847,
        14 => 0x0E60D,
    ];

    /**
     * Format information strings for EC Level L (01) and masks 0..7.
     */
    private const FORMAT_INFO_L = [
        0 => 0x77C4,
        1 => 0x72F3,
        2 => 0x7DAA,
        3 => 0x789D,
        4 => 0x662F,
        5 => 0x6318,
        6 => 0x6C41,
        7 => 0x6976,
    ];

    public static function dataUri(string $data, int $size = 220, int $margin = 4): string
    {
        $svg = self::render($data, $size, $margin);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public static function render(string $data, int $size = 220, int $margin = 4): string
    {
        $matrix = self::encodeMatrix($data);
        $dimension = count($matrix);
        $totalModules = $dimension + ($margin * 2);

        $paths = [];
        for ($r = 0; $r < $dimension; $r++) {
            for ($c = 0; $c < $dimension; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = $c + $margin;
                    $y = $r + $margin;
                    $paths[] = "M{$x},{$y}h1v1h-1z";
                }
            }
        }

        $pathData = implode('', $paths);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$totalModules} {$totalModules}" width="{$size}" height="{$size}" shape-rendering="crispEdges">
<rect width="100%" height="100%" fill="#ffffff"/>
<path d="{$pathData}" fill="#0f172a"/>
</svg>
SVG;
    }

    /**
     * @return array<int, array<int, int>>
     */
    public static function encodeMatrix(string $data): array
    {
        $len = strlen($data);
        $version = self::pickVersion($len);
        $spec = self::VERSION_TABLE_L[$version];
        $totalDataBytes = $spec[0];

        // Build bit stream in Byte mode (0100)
        $bits = '0100';
        $countBits = $version <= 9 ? 8 : 16;
        $bits .= str_pad(decbin($len), $countBits, '0', STR_PAD_LEFT);

        for ($i = 0; $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $maxDataBits = $totalDataBytes * 8;
        $terminator = min(4, $maxDataBits - strlen($bits));
        if ($terminator > 0) {
            $bits .= str_repeat('0', $terminator);
        }

        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - (strlen($bits) % 8));
        }

        $padBytes = ['11101100', '00010001'];
        $padIdx = 0;
        while (strlen($bits) < $maxDataBits) {
            $bits .= $padBytes[$padIdx % 2];
            $padIdx++;
        }

        $dataBytes = [];
        foreach (str_split($bits, 8) as $byteBits) {
            $dataBytes[] = bindec($byteBits);
        }

        $finalCodewords = self::interleaveWithEcc($dataBytes, $spec);
        $bitStream = '';
        foreach ($finalCodewords as $cw) {
            $bitStream .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        $dimension = 17 + ($version * 4);
        [$baseMatrix, $reserved] = self::buildFunctionPatterns($version, $dimension);
        $placedMatrix = self::placeDataBits($baseMatrix, $reserved, $bitStream, $dimension);

        // Choose mask with lowest penalty score
        $bestMatrix = null;
        $bestPenalty = PHP_INT_MAX;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = self::applyMask($placedMatrix, $reserved, $mask, $dimension);
            $candidate = self::writeFormatAndVersionInfo($candidate, $version, $mask, $dimension);
            $penalty = self::calculatePenalty($candidate, $dimension);
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestMatrix = $candidate;
            }
        }

        return $bestMatrix;
    }

    private static function pickVersion(int $byteLength): int
    {
        foreach (self::VERSION_TABLE_L as $ver => $spec) {
            $capacityBits = $spec[0] * 8;
            $headerBits = 4 + ($ver <= 9 ? 8 : 16);
            $maxBytes = intdiv($capacityBits - $headerBits, 8);
            if ($byteLength <= $maxBytes) {
                return $ver;
            }
        }

        throw new InvalidArgumentException('QR payload exceeds supported length.');
    }

    /**
     * @param array<int, int> $dataBytes
     * @param array{0:int,1:int,2:int,3:int,4:int,5:int} $spec
     * @return array<int, int>
     */
    private static function interleaveWithEcc(array $dataBytes, array $spec): array
    {
        [, $ecLen, $blocksG1, $dataLenG1, $blocksG2, $dataLenG2] = $spec;

        $dataBlocks = [];
        $eccBlocks = [];
        $offset = 0;

        for ($i = 0; $i < $blocksG1; $i++) {
            $block = array_slice($dataBytes, $offset, $dataLenG1);
            $offset += $dataLenG1;
            $dataBlocks[] = $block;
            $eccBlocks[] = self::reedSolomonEncode($block, $ecLen);
        }

        for ($i = 0; $i < $blocksG2; $i++) {
            $block = array_slice($dataBytes, $offset, $dataLenG2);
            $offset += $dataLenG2;
            $dataBlocks[] = $block;
            $eccBlocks[] = self::reedSolomonEncode($block, $ecLen);
        }

        $result = [];
        $maxDataLen = max($dataLenG1, $dataLenG2);
        for ($col = 0; $col < $maxDataLen; $col++) {
            foreach ($dataBlocks as $block) {
                if (isset($block[$col])) {
                    $result[] = $block[$col];
                }
            }
        }

        for ($col = 0; $col < $ecLen; $col++) {
            foreach ($eccBlocks as $block) {
                $result[] = $block[$col];
            }
        }

        return $result;
    }

    /**
     * @param array<int, int> $data
     * @return array<int, int>
     */
    private static function reedSolomonEncode(array $data, int $ecCount): array
    {
        [$exp, $log] = self::gfTables();

        $gen = [1];
        for ($i = 0; $i < $ecCount; $i++) {
            $next = array_fill(0, count($gen) + 1, 0);
            $root = $exp[$i];
            for ($j = 0, $gLen = count($gen); $j < $gLen; $j++) {
                $next[$j] ^= $gen[$j];
                $next[$j + 1] ^= self::gfMul($gen[$j], $root, $exp, $log);
            }
            $gen = $next;
        }

        $rem = array_fill(0, $ecCount, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($rem);
            $rem[] = 0;
            if ($factor !== 0) {
                for ($j = 0; $j < $ecCount; $j++) {
                    $rem[$j] ^= self::gfMul($gen[$j + 1], $factor, $exp, $log);
                }
            }
        }

        return $rem;
    }

    /**
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    private static function gfTables(): array
    {
        static $tables = null;
        if ($tables !== null) {
            return $tables;
        }

        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 255];
        }

        $tables = [$exp, $log];

        return $tables;
    }

    private static function gfMul(int $a, int $b, array $exp, array $log): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return $exp[$log[$a] + $log[$b]];
    }

    /**
     * @return array{0: array<int, array<int, int>>, 1: array<int, array<int, bool>>}
     */
    private static function buildFunctionPatterns(int $version, int $dim): array
    {
        $matrix = array_fill(0, $dim, array_fill(0, $dim, 0));
        $reserved = array_fill(0, $dim, array_fill(0, $dim, false));

        // Finder patterns + separators
        self::placeFinder($matrix, $reserved, 0, 0, $dim);
        self::placeFinder($matrix, $reserved, 0, $dim - 7, $dim);
        self::placeFinder($matrix, $reserved, $dim - 7, 0, $dim);

        // Timing patterns
        for ($i = 8; $i < $dim - 8; $i++) {
            $bit = ($i % 2 === 0) ? 1 : 0;
            if (!$reserved[6][$i]) {
                $matrix[6][$i] = $bit;
                $reserved[6][$i] = true;
            }
            if (!$reserved[$i][6]) {
                $matrix[$i][6] = $bit;
                $reserved[$i][6] = true;
            }
        }

        // Alignment patterns
        $coords = self::ALIGNMENT_PATTERNS[$version] ?? [];
        $cCount = count($coords);
        for ($i = 0; $i < $cCount; $i++) {
            for ($j = 0; $j < $cCount; $j++) {
                if (
                    ($i === 0 && $j === 0)
                    || ($i === 0 && $j === $cCount - 1)
                    || ($i === $cCount - 1 && $j === 0)
                ) {
                    continue;
                }
                self::placeAlignment($matrix, $reserved, $coords[$i], $coords[$j]);
            }
        }

        // Dark module + reserve format info areas
        $matrix[$dim - 8][8] = 1;
        $reserved[$dim - 8][8] = true;

        for ($i = 0; $i < 9; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = $dim - 8; $i < $dim; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }

        if ($version >= 7) {
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $reserved[$i][$dim - 11 + $j] = true;
                    $reserved[$dim - 11 + $j][$i] = true;
                }
            }
        }

        return [$matrix, $reserved];
    }

    private static function placeFinder(array &$matrix, array &$reserved, int $row, int $col, int $dim): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $rr = $row + $r;
                $cc = $col + $c;
                if ($rr < 0 || $rr >= $dim || $cc < 0 || $cc >= $dim) {
                    continue;
                }
                $inOuter = ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6);
                $onBorder = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                $inInner = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);

                $matrix[$rr][$cc] = ($inOuter && ($onBorder || $inInner)) ? 1 : 0;
                $reserved[$rr][$cc] = true;
            }
        }
    }

    private static function placeAlignment(array &$matrix, array &$reserved, int $centerRow, int $centerCol): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $rr = $centerRow + $r;
                $cc = $centerCol + $c;
                $isDark = (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0));
                $matrix[$rr][$cc] = $isDark ? 1 : 0;
                $reserved[$rr][$cc] = true;
            }
        }
    }

    private static function placeDataBits(array $matrix, array $reserved, string $bits, int $dim): array
    {
        $bitIdx = 0;
        $bitLen = strlen($bits);
        $upward = true;

        for ($col = $dim - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                $col--;
            }
            for ($i = 0; $i < $dim; $i++) {
                $row = $upward ? ($dim - 1 - $i) : $i;
                for ($c = 0; $c < 2; $c++) {
                    $cc = $col - $c;
                    if (!$reserved[$row][$cc]) {
                        $matrix[$row][$cc] = ($bitIdx < $bitLen && $bits[$bitIdx] === '1') ? 1 : 0;
                        $bitIdx++;
                    }
                }
            }
            $upward = !$upward;
        }

        return $matrix;
    }

    private static function applyMask(array $matrix, array $reserved, int $mask, int $dim): array
    {
        for ($r = 0; $r < $dim; $r++) {
            for ($c = 0; $c < $dim; $c++) {
                if ($reserved[$r][$c]) {
                    continue;
                }
                $invert = match ($mask) {
                    0 => (($r + $c) % 2) === 0,
                    1 => ($r % 2) === 0,
                    2 => ($c % 3) === 0,
                    3 => (($r + $c) % 3) === 0,
                    4 => ((intdiv($r, 2) + intdiv($c, 3)) % 2) === 0,
                    5 => ((($r * $c) % 2) + (($r * $c) % 3)) === 0,
                    6 => (((($r * $c) % 2) + (($r * $c) % 3)) % 2) === 0,
                    7 => (((($r + $c) % 2) + (($r * $c) % 3)) % 2) === 0,
                    default => false,
                };
                if ($invert) {
                    $matrix[$r][$c] ^= 1;
                }
            }
        }

        return $matrix;
    }

    private static function writeFormatAndVersionInfo(array $matrix, int $version, int $mask, int $dim): array
    {
        $formatBits = self::FORMAT_INFO_L[$mask];

        // Around top-left finder
        $coords1 = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8],
        ];
        // Split across bottom-left and top-right
        $coords2 = [
            [$dim - 1, 8], [$dim - 2, 8], [$dim - 3, 8], [$dim - 4, 8], [$dim - 5, 8], [$dim - 6, 8], [$dim - 7, 8],
            [8, $dim - 8], [8, $dim - 7], [8, $dim - 6], [8, $dim - 5], [8, $dim - 4], [8, $dim - 3], [8, $dim - 2], [8, $dim - 1],
        ];

        for ($i = 0; $i < 15; $i++) {
            $bit = ($formatBits >> (14 - $i)) & 1;
            [$r1, $c1] = $coords1[$i];
            [$r2, $c2] = $coords2[$i];
            $matrix[$r1][$c1] = $bit;
            $matrix[$r2][$c2] = $bit;
        }

        if ($version >= 7 && isset(self::VERSION_INFO_BITS[$version])) {
            $vBits = self::VERSION_INFO_BITS[$version];
            for ($i = 0; $i < 18; $i++) {
                $bit = ($vBits >> $i) & 1;
                $r = intdiv($i, 3);
                $c = ($dim - 11) + ($i % 3);
                $matrix[$r][$c] = $bit;
                $matrix[$c][$r] = $bit;
            }
        }

        return $matrix;
    }

    private static function calculatePenalty(array $matrix, int $dim): int
    {
        $penalty = 0;

        // Rule 1: runs of 5+ same-colored modules in rows/cols
        for ($i = 0; $i < $dim; $i++) {
            $rowRun = 1;
            $colRun = 1;
            for ($j = 1; $j < $dim; $j++) {
                if ($matrix[$i][$j] === $matrix[$i][$j - 1]) {
                    $rowRun++;
                    if ($rowRun === 5) {
                        $penalty += 3;
                    } elseif ($rowRun > 5) {
                        $penalty++;
                    }
                } else {
                    $rowRun = 1;
                }

                if ($matrix[$j][$i] === $matrix[$j - 1][$i]) {
                    $colRun++;
                    if ($colRun === 5) {
                        $penalty += 3;
                    } elseif ($colRun > 5) {
                        $penalty++;
                    }
                } else {
                    $colRun = 1;
                }
            }
        }

        // Rule 2: 2x2 blocks
        for ($r = 0; $r < $dim - 1; $r++) {
            for ($c = 0; $c < $dim - 1; $c++) {
                $val = $matrix[$r][$c];
                if (
                    $val === $matrix[$r][$c + 1]
                    && $val === $matrix[$r + 1][$c]
                    && $val === $matrix[$r + 1][$c + 1]
                ) {
                    $penalty += 3;
                }
            }
        }

        return $penalty;
    }
}
