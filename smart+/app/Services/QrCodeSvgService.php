<?php

namespace App\Services;

/**
 * Pure-PHP ISO/IEC 18004 QR Code SVG generator (Byte mode, ECC Level M).
 * Zero external dependencies — generates crisp offline vector QR codes for archived documents.
 */
class QrCodeSvgService
{
    /**
     * Alignment pattern center coordinates per version (versions 1-10).
     */
    private const ALIGNMENT_POSITIONS = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
        5 => [6, 30],
        6 => [6, 34],
        7 => [6, 22, 38],
        8 => [6, 24, 42],
        9 => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /**
     * ECC Level M block specs per version: [eccPerBlock, [group1Blocks, group1DataLen, group2Blocks, group2DataLen], maxBytes]
     */
    private const VERSION_SPECS = [
        1 => [10, [1, 16, 0, 0], 14],
        2 => [16, [1, 28, 0, 0], 26],
        3 => [26, [1, 44, 0, 0], 42],
        4 => [18, [2, 32, 0, 0], 62],
        5 => [24, [2, 43, 0, 0], 84],
        6 => [16, [4, 27, 0, 0], 106],
        7 => [18, [4, 31, 0, 0], 122],
        8 => [22, [2, 38, 2, 39], 152],
        9 => [22, [3, 36, 2, 37], 180],
        10 => [26, [4, 43, 1, 44], 213],
    ];

    public static function svg(string $text, int $size = 76, int $margin = 2): string
    {
        $matrix = self::encodeMatrix($text);
        $dim = count($matrix);
        $total = $dim + ($margin * 2);

        $paths = [];
        for ($y = 0; $y < $dim; $y++) {
            for ($x = 0; $x < $dim; $x++) {
                if ($matrix[$y][$x] === 1) {
                    $px = $x + $margin;
                    $py = $y + $margin;
                    $paths[] = "M{$px},{$py}h1v1h-1z";
                }
            }
        }

        $d = implode('', $paths);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $total . ' ' . $total . '" width="' . $size . '" height="' . $size . '" shape-rendering="crispEdges" role="img" aria-label="QR Verifikasi Rapor">'
            . '<rect width="' . $total . '" height="' . $total . '" fill="#ffffff"/>'
            . '<path d="' . $d . '" fill="#000000"/>'
            . '</svg>';
    }

    /**
     * Build the masked 2D QR module matrix (0 = white, 1 = black).
     */
    public static function encodeMatrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $len = count($bytes);

        $version = 10;
        foreach (self::VERSION_SPECS as $v => [, , $maxBytes]) {
            if ($len <= $maxBytes) {
                $version = $v;
                break;
            }
        }
        if ($len > self::VERSION_SPECS[$version][2]) {
            $bytes = array_slice($bytes, 0, self::VERSION_SPECS[$version][2]);
            $len = count($bytes);
        }

        [$eccLen, [$g1Blocks, $g1Data, $g2Blocks, $g2Data]] = self::VERSION_SPECS[$version];
        $totalDataCodewords = ($g1Blocks * $g1Data) + ($g2Blocks * $g2Data);

        // 1. Encode data bits (Byte mode 0100 + count + bytes + terminator + pad codewords)
        $bits = [];
        $pushBits = static function (int $val, int $width) use (&$bits): void {
            for ($i = $width - 1; $i >= 0; $i--) {
                $bits[] = ($val >> $i) & 1;
            }
        };

        $pushBits(0b0100, 4); // Byte mode
        $pushBits($len, $version <= 9 ? 8 : 16);
        foreach ($bytes as $b) {
            $pushBits($b, 8);
        }

        $maxBits = $totalDataCodewords * 8;
        $terminatorLen = min(4, $maxBits - count($bits));
        for ($i = 0; $i < $terminatorLen; $i++) {
            $bits[] = 0;
        }
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $cw = 0;
            for ($j = 0; $j < 8; $j++) {
                $cw = ($cw << 1) | $bits[$i + $j];
            }
            $codewords[] = $cw;
        }

        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (count($codewords) < $totalDataCodewords) {
            $codewords[] = $padBytes[$padIdx % 2];
            $padIdx++;
        }

        // 2. Split into RS blocks and compute ECC codewords
        $dataBlocks = [];
        $eccBlocks = [];
        $offset = 0;
        $gen = self::rsGenerator($eccLen);

        for ($i = 0; $i < $g1Blocks; $i++) {
            $block = array_slice($codewords, $offset, $g1Data);
            $offset += $g1Data;
            $dataBlocks[] = $block;
            $eccBlocks[] = self::rsRemainder($block, $gen, $eccLen);
        }
        for ($i = 0; $i < $g2Blocks; $i++) {
            $block = array_slice($codewords, $offset, $g2Data);
            $offset += $g2Data;
            $dataBlocks[] = $block;
            $eccBlocks[] = self::rsRemainder($block, $gen, $eccLen);
        }

        // 3. Interleave data and ECC codewords
        $finalStream = [];
        $maxDataLen = max($g1Data, $g2Data);
        for ($i = 0; $i < $maxDataLen; $i++) {
            foreach ($dataBlocks as $block) {
                if (isset($block[$i])) {
                    $finalStream[] = $block[$i];
                }
            }
        }
        for ($i = 0; $i < $eccLen; $i++) {
            foreach ($eccBlocks as $block) {
                $finalStream[] = $block[$i];
            }
        }

        // 4. Construct QR matrix with function patterns
        $size = $version * 4 + 17;
        $grid = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        self::placeFinder($grid, $reserved, 0, 0);
        self::placeFinder($grid, $reserved, $size - 7, 0);
        self::placeFinder($grid, $reserved, 0, $size - 7);

        // Separators
        for ($i = 0; $i < 8; $i++) {
            self::setFunctionModule($grid, $reserved, 7, $i, 0);
            self::setFunctionModule($grid, $reserved, $i, 7, 0);
            self::setFunctionModule($grid, $reserved, $size - 8, $i, 0);
            self::setFunctionModule($grid, $reserved, $size - 1 - $i, 7, 0);
            self::setFunctionModule($grid, $reserved, $i, $size - 8, 0);
            self::setFunctionModule($grid, $reserved, 7, $size - 1 - $i, 0);
        }

        // Alignment patterns
        $alignCoords = self::ALIGNMENT_POSITIONS[$version];
        $alignCount = count($alignCoords);
        for ($rIdx = 0; $rIdx < $alignCount; $rIdx++) {
            for ($cIdx = 0; $cIdx < $alignCount; $cIdx++) {
                if (
                    ($rIdx === 0 && $cIdx === 0)
                    || ($rIdx === 0 && $cIdx === $alignCount - 1)
                    || ($rIdx === $alignCount - 1 && $cIdx === 0)
                ) {
                    continue;
                }
                self::placeAlignment($grid, $reserved, $alignCoords[$cIdx], $alignCoords[$rIdx]);
            }
        }

        // Timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $bit = ($i % 2 === 0) ? 1 : 0;
            self::setFunctionModule($grid, $reserved, $i, 6, $bit);
            self::setFunctionModule($grid, $reserved, 6, $i, $bit);
        }

        // Dark module
        self::setFunctionModule($grid, $reserved, 8, $size - 8, 1);

        // Reserve format info areas
        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $reserved[$i][8] = true;
                $reserved[8][$i] = true;
            }
        }
        for ($i = 0; $i < 8; $i++) {
            $reserved[8][$size - 1 - $i] = true;
            if ($i < 7) {
                $reserved[$size - 1 - $i][8] = true;
            }
        }

        // Version info for v7+
        if ($version >= 7) {
            self::placeVersionInfo($grid, $reserved, $version, $size);
        }

        // 5. Place data bits in zig-zag order
        $bitStream = [];
        foreach ($finalStream as $cw) {
            for ($b = 7; $b >= 0; $b--) {
                $bitStream[] = ($cw >> $b) & 1;
            }
        }

        $bitIdx = 0;
        $bitTotal = count($bitStream);
        $up = true;
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $size; $vert++) {
                $y = $up ? ($size - 1 - $vert) : $vert;
                for ($dx = 0; $dx < 2; $dx++) {
                    $x = $right - $dx;
                    if (!$reserved[$y][$x]) {
                        $grid[$y][$x] = ($bitIdx < $bitTotal) ? $bitStream[$bitIdx++] : 0;
                    }
                }
            }
            $up = !$up;
        }

        // 6. Choose optimal mask pattern (0-7) with lowest penalty score
        $bestMask = 0;
        $bestScore = PHP_INT_MAX;
        $bestGrid = $grid;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = $grid;
            for ($y = 0; $y < $size; $y++) {
                for ($x = 0; $x < $size; $x++) {
                    if (!$reserved[$y][$x] && self::maskCondition($mask, $x, $y)) {
                        $candidate[$y][$x] ^= 1;
                    }
                }
            }
            self::placeFormatInfo($candidate, $size, $mask);
            $score = self::penaltyScore($candidate, $size);
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestMask = $mask;
                $bestGrid = $candidate;
            }
        }

        return $bestGrid;
    }

    private static function setFunctionModule(array &$grid, array &$reserved, int $x, int $y, int $val): void
    {
        $grid[$y][$x] = $val;
        $reserved[$y][$x] = true;
    }

    private static function placeFinder(array &$grid, array &$reserved, int $ox, int $oy): void
    {
        for ($dy = 0; $dy < 7; $dy++) {
            for ($dx = 0; $dx < 7; $dx++) {
                $isDark = ($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4)) ? 1 : 0;
                self::setFunctionModule($grid, $reserved, $ox + $dx, $oy + $dy, $isDark);
            }
        }
    }

    private static function placeAlignment(array &$grid, array &$reserved, int $cx, int $cy): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $dist = max(abs($dx), abs($dy));
                $val = ($dist === 2 || $dist === 0) ? 1 : 0;
                self::setFunctionModule($grid, $reserved, $cx + $dx, $cy + $dy, $val);
            }
        }
    }

    private static function placeFormatInfo(array &$grid, int $size, int $mask): void
    {
        // ECC Level M = 00
        $data = (0b00 << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ ((($rem >> 9) & 1) * 0x537);
        }
        $bits = (($data << 10) | ($rem & 0x3FF)) ^ 0x5412;

        for ($i = 0; $i <= 5; $i++) {
            $grid[8][$i] = ($bits >> (14 - $i)) & 1;
            $grid[$size - 1 - $i][8] = ($bits >> $i) & 1;
        }
        $grid[8][7] = ($bits >> 8) & 1;
        $grid[8][8] = ($bits >> 7) & 1;
        $grid[7][8] = ($bits >> 6) & 1;
        $grid[$size - 7][8] = ($bits >> 6) & 1;

        for ($i = 0; $i <= 5; $i++) {
            $grid[5 - $i][8] = ($bits >> (5 - $i)) & 1;
        }
        for ($i = 0; $i <= 7; $i++) {
            $grid[8][$size - 8 + $i] = ($bits >> (7 - $i)) & 1;
        }
    }

    private static function placeVersionInfo(array &$grid, array &$reserved, int $version, int $size): void
    {
        $rem = $version;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ ((($rem >> 11) & 1) * 0x1F25);
        }
        $bits = ($version << 12) | ($rem & 0xFFF);

        for ($i = 0; $i < 18; $i++) {
            $bit = ($bits >> $i) & 1;
            $a = $size - 11 + ($i % 3);
            $b = intdiv($i, 3);
            self::setFunctionModule($grid, $reserved, $b, $a, $bit);
            self::setFunctionModule($grid, $reserved, $a, $b, $bit);
        }
    }

    private static function maskCondition(int $mask, int $x, int $y): bool
    {
        return match ($mask) {
            0 => (($x + $y) % 2) === 0,
            1 => ($y % 2) === 0,
            2 => ($x % 3) === 0,
            3 => (($x + $y) % 3) === 0,
            4 => ((intdiv($y, 2) + intdiv($x, 3)) % 2) === 0,
            5 => ((($x * $y) % 2) + (($x * $y) % 3)) === 0,
            6 => (((($x * $y) % 2) + (($x * $y) % 3)) % 2) === 0,
            7 => (((($x + $y) % 2) + (($x * $y) % 3)) % 2) === 0,
            default => false,
        };
    }

    private static function penaltyScore(array $grid, int $size): int
    {
        $penalty = 0;

        // N1: 5+ same colored adjacent modules in row/col
        for ($y = 0; $y < $size; $y++) {
            $runColor = $grid[$y][0];
            $runLen = 1;
            for ($x = 1; $x < $size; $x++) {
                if ($grid[$y][$x] === $runColor) {
                    $runLen++;
                } else {
                    if ($runLen >= 5) {
                        $penalty += 3 + ($runLen - 5);
                    }
                    $runColor = $grid[$y][$x];
                    $runLen = 1;
                }
            }
            if ($runLen >= 5) {
                $penalty += 3 + ($runLen - 5);
            }
        }
        for ($x = 0; $x < $size; $x++) {
            $runColor = $grid[0][$x];
            $runLen = 1;
            for ($y = 1; $y < $size; $y++) {
                if ($grid[$y][$x] === $runColor) {
                    $runLen++;
                } else {
                    if ($runLen >= 5) {
                        $penalty += 3 + ($runLen - 5);
                    }
                    $runColor = $grid[$y][$x];
                    $runLen = 1;
                }
            }
            if ($runLen >= 5) {
                $penalty += 3 + ($runLen - 5);
            }
        }

        // N2: 2x2 blocks of same color
        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                $c = $grid[$y][$x];
                if ($c === $grid[$y][$x + 1] && $c === $grid[$y + 1][$x] && $c === $grid[$y + 1][$x + 1]) {
                    $penalty += 3;
                }
            }
        }

        // N4: dark ratio deviation from 50%
        $dark = 0;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $dark += $grid[$y][$x];
            }
        }
        $percent = (int) (($dark * 100) / ($size * $size));
        $penalty += intdiv(abs($percent - 50), 5) * 10;

        return $penalty;
    }

    private static function gfMultiply(int $a, int $b): int
    {
        $res = 0;
        for ($i = 0; $i < 8; $i++) {
            if (($b & 1) !== 0) {
                $res ^= $a;
            }
            $hi = $a & 0x80;
            $a = ($a << 1) & 0xFF;
            if ($hi !== 0) {
                $a ^= 0x1D;
            }
            $b >>= 1;
        }
        return $res;
    }

    private static function rsGenerator(int $degree): array
    {
        $poly = [1];
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            for ($j = 0; $j < count($poly); $j++) {
                $next[$j] ^= $poly[$j];
                $next[$j + 1] ^= self::gfMultiply($poly[$j], $root);
            }
            $poly = $next;
            $root = self::gfMultiply($root, 2);
        }
        return $poly;
    }

    private static function rsRemainder(array $data, array $gen, int $eccLen): array
    {
        $res = array_merge($data, array_fill(0, $eccLen, 0));
        $dataLen = count($data);
        for ($i = 0; $i < $dataLen; $i++) {
            $factor = $res[$i];
            if ($factor === 0) {
                continue;
            }
            for ($j = 0; $j < count($gen); $j++) {
                $res[$i + $j] ^= self::gfMultiply($gen[$j], $factor);
            }
        }
        return array_slice($res, $dataLen, $eccLen);
    }
}
