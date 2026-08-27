<?php

namespace App\Core;

/**
 * Dependency-free QR Code encoder (Model 2, versions 1-40, all 4 error
 * correction levels). Faithful PHP port of the "QR Code generator library"
 * by Project Nayuki (MIT License, https://www.nayuki.io/page/qr-code-generator-library),
 * trimmed to the numeric/alphanumeric/byte segment modes actually needed here
 * (Kanji mode is not implemented, matching the reference library's own scope).
 *
 * Kept dependency-free (no Composer, no GD) so it can generate the matrix used
 * both for inline SVG (email/HTML previews) and for direct rectangle drawing
 * into a PDF (ticket attachments) — see TicketPdf.
 */
class QrCode
{
    public const ECC_LOW = 0;
    public const ECC_MEDIUM = 1;
    public const ECC_QUARTILE = 2;
    public const ECC_HIGH = 3;

    private const MIN_VERSION = 1;
    private const MAX_VERSION = 40;

    private const PENALTY_N1 = 3;
    private const PENALTY_N2 = 3;
    private const PENALTY_N3 = 40;
    private const PENALTY_N4 = 10;

    // Indexed [ecl][version], index 0 unused.
    private const ECC_CODEWORDS_PER_BLOCK = [
        [-1, 7, 10, 15, 20, 26, 18, 20, 24, 30, 18, 20, 24, 26, 30, 22, 24, 28, 30, 28, 28, 28, 28, 30, 30, 26, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
        [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28],
        [-1, 13, 22, 18, 26, 18, 24, 18, 22, 20, 24, 28, 26, 24, 20, 30, 24, 28, 28, 26, 30, 28, 30, 30, 30, 30, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
        [-1, 17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24, 24, 30, 28, 28, 26, 28, 30, 24, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
    ];

    private const NUM_ERROR_CORRECTION_BLOCKS = [
        [-1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 4, 4, 4, 4, 4, 6, 6, 6, 6, 7, 8, 8, 9, 9, 10, 12, 12, 12, 13, 14, 15, 16, 17, 18, 19, 19, 20, 21, 22, 24, 25],
        [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49],
        [-1, 1, 1, 2, 2, 4, 4, 6, 6, 8, 8, 8, 10, 12, 16, 12, 17, 16, 18, 21, 20, 23, 23, 25, 27, 29, 34, 34, 35, 38, 40, 43, 45, 48, 51, 53, 56, 59, 62, 65, 68],
        [-1, 1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16, 18, 16, 19, 21, 25, 25, 25, 34, 30, 32, 35, 37, 40, 42, 45, 48, 51, 54, 57, 60, 63, 66, 70, 74, 77, 81],
    ];

    // ecl => format bits (uint2)
    private const ECC_FORMAT_BITS = [self::ECC_LOW => 1, self::ECC_MEDIUM => 0, self::ECC_QUARTILE => 3, self::ECC_HIGH => 2];

    private const ALPHANUMERIC_CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

    // Mode indicator bits, and [numCharCountBits for version ranges 1-9, 10-26, 27-40]
    private const MODE_NUMERIC = [0x1, [10, 12, 14]];
    private const MODE_ALPHANUMERIC = [0x2, [9, 11, 13]];
    private const MODE_BYTE = [0x4, [8, 16, 16]];

    public int $version;
    public int $size;
    public int $mask;
    public int $errorCorrectionLevel;
    /** @var bool[][] */
    private array $modules;
    /** @var bool[][] */
    private array $isFunction;

    public static function encodeText(string $text, int $ecl = self::ECC_MEDIUM): self
    {
        $segs = self::makeSegments($text);
        return self::encodeSegments($segs, $ecl);
    }

    /** @param array<int,array{0:int,1:int,2:int[]}> $segs list of [modeBits, numChars, bitData[]] */
    private static function encodeSegments(array $segs, int $ecl, int $minVersion = 1, int $maxVersion = 40): self
    {
        $version = $minVersion;
        $dataUsedBits = 0;
        for (; ; $version++) {
            $dataCapacityBits = self::getNumDataCodewords($version, $ecl) * 8;
            $usedBits = self::getTotalBits($segs, $version);
            if ($usedBits !== null && $usedBits <= $dataCapacityBits) {
                $dataUsedBits = $usedBits;
                break;
            }
            if ($version >= $maxVersion) {
                throw new \RuntimeException('Données trop longues pour un QR code.');
            }
        }

        foreach ([self::ECC_MEDIUM, self::ECC_QUARTILE, self::ECC_HIGH] as $newEcl) {
            if ($dataUsedBits <= self::getNumDataCodewords($version, $newEcl) * 8) {
                $ecl = $newEcl;
            }
        }

        $bb = [];
        foreach ($segs as [$modeBits, $numChars, $bitData]) {
            self::appendBits($modeBits, 4, $bb);
            self::appendBits($numChars, self::numCharCountBits($modeBits, $version), $bb);
            foreach ($bitData as $b) {
                $bb[] = $b;
            }
        }

        $dataCapacityBits = self::getNumDataCodewords($version, $ecl) * 8;
        self::appendBits(0, min(4, $dataCapacityBits - count($bb)), $bb);
        self::appendBits(0, (8 - count($bb) % 8) % 8, $bb);

        for ($padByte = 0xEC; count($bb) < $dataCapacityBits; $padByte ^= 0xEC ^ 0x11) {
            self::appendBits($padByte, 8, $bb);
        }

        $dataCodewords = array_fill(0, intdiv(count($bb) + 7, 8), 0);
        foreach ($bb as $i => $b) {
            $dataCodewords[$i >> 3] |= $b << (7 - ($i & 7));
        }

        return new self($version, $ecl, $dataCodewords, -1);
    }

    private function __construct(int $version, int $ecl, array $dataCodewords, int $msk)
    {
        $this->version = $version;
        $this->errorCorrectionLevel = $ecl;
        $this->size = $version * 4 + 17;

        $row = array_fill(0, $this->size, false);
        $this->modules = [];
        $this->isFunction = [];
        for ($i = 0; $i < $this->size; $i++) {
            $this->modules[] = $row;
            $this->isFunction[] = $row;
        }

        $this->drawFunctionPatterns();
        $allCodewords = $this->addEccAndInterleave($dataCodewords);
        $this->drawCodewords($allCodewords);

        if ($msk === -1) {
            $minPenalty = PHP_INT_MAX;
            for ($i = 0; $i < 8; $i++) {
                $this->applyMask($i);
                $this->drawFormatBits($i);
                $penalty = $this->getPenaltyScore();
                if ($penalty < $minPenalty) {
                    $msk = $i;
                    $minPenalty = $penalty;
                }
                $this->applyMask($i);
            }
        }
        $this->mask = $msk;
        $this->applyMask($msk);
        $this->drawFormatBits($msk);
    }

    public function getModule(int $x, int $y): bool
    {
        return $x >= 0 && $x < $this->size && $y >= 0 && $y < $this->size && $this->modules[$y][$x];
    }

    /** Renders this QR code as a standalone, theme-safe inline SVG string. */
    public function toSvg(int $border = 2, string $dark = '#000000', string $light = '#ffffff'): string
    {
        $dim = $this->size + $border * 2;
        $path = '';
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                if ($this->getModule($x, $y)) {
                    $path .= 'M' . ($x + $border) . ',' . ($y + $border) . 'h1v1h-1z';
                }
            }
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $dim . ' ' . $dim . '" shape-rendering="crispEdges">'
            . '<rect width="' . $dim . '" height="' . $dim . '" fill="' . htmlspecialchars($light, ENT_QUOTES) . '"/>'
            . '<path d="' . $path . '" fill="' . htmlspecialchars($dark, ENT_QUOTES) . '"/>'
            . '</svg>';
    }

    // ---- Function pattern drawing ----

    private function drawFunctionPatterns(): void
    {
        for ($i = 0; $i < $this->size; $i++) {
            $this->setFunctionModule(6, $i, $i % 2 === 0);
            $this->setFunctionModule($i, 6, $i % 2 === 0);
        }

        $this->drawFinderPattern(3, 3);
        $this->drawFinderPattern($this->size - 4, 3);
        $this->drawFinderPattern(3, $this->size - 4);

        $alignPatPos = $this->getAlignmentPatternPositions();
        $numAlign = count($alignPatPos);
        for ($i = 0; $i < $numAlign; $i++) {
            for ($j = 0; $j < $numAlign; $j++) {
                if (!($i === 0 && $j === 0 || $i === 0 && $j === $numAlign - 1 || $i === $numAlign - 1 && $j === 0)) {
                    $this->drawAlignmentPattern($alignPatPos[$i], $alignPatPos[$j]);
                }
            }
        }

        $this->drawFormatBits(0);
        $this->drawVersion();
    }

    private function drawFormatBits(int $mask): void
    {
        $data = self::ECC_FORMAT_BITS[$this->errorCorrectionLevel] << 3 | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = ($data << 10 | $rem) ^ 0x5412;

        for ($i = 0; $i <= 5; $i++) {
            $this->setFunctionModule(8, $i, self::getBit($bits, $i));
        }
        $this->setFunctionModule(8, 7, self::getBit($bits, 6));
        $this->setFunctionModule(8, 8, self::getBit($bits, 7));
        $this->setFunctionModule(7, 8, self::getBit($bits, 8));
        for ($i = 9; $i < 15; $i++) {
            $this->setFunctionModule(14 - $i, 8, self::getBit($bits, $i));
        }

        for ($i = 0; $i < 8; $i++) {
            $this->setFunctionModule($this->size - 1 - $i, 8, self::getBit($bits, $i));
        }
        for ($i = 8; $i < 15; $i++) {
            $this->setFunctionModule(8, $this->size - 15 + $i, self::getBit($bits, $i));
        }
        $this->setFunctionModule(8, $this->size - 8, true);
    }

    private function drawVersion(): void
    {
        if ($this->version < 7) {
            return;
        }
        $rem = $this->version;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        }
        $bits = $this->version << 12 | $rem;

        for ($i = 0; $i < 18; $i++) {
            $color = self::getBit($bits, $i);
            $a = $this->size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $this->setFunctionModule($a, $b, $color);
            $this->setFunctionModule($b, $a, $color);
        }
    }

    private function drawFinderPattern(int $x, int $y): void
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $dist = max(abs($dx), abs($dy));
                $xx = $x + $dx;
                $yy = $y + $dy;
                if ($xx >= 0 && $xx < $this->size && $yy >= 0 && $yy < $this->size) {
                    $this->setFunctionModule($xx, $yy, $dist !== 2 && $dist !== 4);
                }
            }
        }
    }

    private function drawAlignmentPattern(int $x, int $y): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $this->setFunctionModule($x + $dx, $y + $dy, max(abs($dx), abs($dy)) !== 1);
            }
        }
    }

    private function setFunctionModule(int $x, int $y, bool $isDark): void
    {
        $this->modules[$y][$x] = $isDark;
        $this->isFunction[$y][$x] = true;
    }

    // ---- Codewords and masking ----

    private function addEccAndInterleave(array $data): array
    {
        $ver = $this->version;
        $ecl = $this->errorCorrectionLevel;

        $numBlocks = self::NUM_ERROR_CORRECTION_BLOCKS[$ecl][$ver];
        $blockEccLen = self::ECC_CODEWORDS_PER_BLOCK[$ecl][$ver];
        $rawCodewords = intdiv(self::getNumRawDataModules($ver), 8);
        $numShortBlocks = $numBlocks - $rawCodewords % $numBlocks;
        $shortBlockLen = intdiv($rawCodewords, $numBlocks);

        $blocks = [];
        $rsDiv = self::reedSolomonComputeDivisor($blockEccLen);
        $k = 0;
        for ($i = 0; $i < $numBlocks; $i++) {
            $len = $shortBlockLen - $blockEccLen + ($i < $numShortBlocks ? 0 : 1);
            $dat = array_slice($data, $k, $len);
            $k += count($dat);
            $ecc = self::reedSolomonComputeRemainder($dat, $rsDiv);
            if ($i < $numShortBlocks) {
                $dat[] = 0;
            }
            $blocks[] = array_merge($dat, $ecc);
        }

        $result = [];
        $blockLen = count($blocks[0]);
        for ($i = 0; $i < $blockLen; $i++) {
            foreach ($blocks as $j => $block) {
                if ($i !== $shortBlockLen - $blockEccLen || $j >= $numShortBlocks) {
                    $result[] = $block[$i];
                }
            }
        }
        return $result;
    }

    private function drawCodewords(array $data): void
    {
        $i = 0;
        $dataLen = count($data);
        for ($right = $this->size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $this->size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? $this->size - 1 - $vert : $vert;
                    if (!$this->isFunction[$y][$x] && $i < $dataLen * 8) {
                        $this->modules[$y][$x] = self::getBit($data[$i >> 3], 7 - ($i & 7));
                        $i++;
                    }
                }
            }
        }
    }

    private function applyMask(int $mask): void
    {
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                $invert = match ($mask) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => $x * $y % 2 + $x * $y % 3 === 0,
                    6 => ($x * $y % 2 + $x * $y % 3) % 2 === 0,
                    7 => (($x + $y) % 2 + $x * $y % 3) % 2 === 0,
                    default => throw new \RuntimeException('Unreachable'),
                };
                if (!$this->isFunction[$y][$x] && $invert) {
                    $this->modules[$y][$x] = !$this->modules[$y][$x];
                }
            }
        }
    }

    private function getPenaltyScore(): int
    {
        $result = 0;

        for ($y = 0; $y < $this->size; $y++) {
            $runColor = false;
            $runX = 0;
            $runHistory = [0, 0, 0, 0, 0, 0, 0];
            for ($x = 0; $x < $this->size; $x++) {
                if ($this->modules[$y][$x] === $runColor) {
                    $runX++;
                    if ($runX === 5) {
                        $result += self::PENALTY_N1;
                    } elseif ($runX > 5) {
                        $result++;
                    }
                } else {
                    $this->finderPenaltyAddHistory($runX, $runHistory);
                    if (!$runColor) {
                        $result += $this->finderPenaltyCountPatterns($runHistory) * self::PENALTY_N3;
                    }
                    $runColor = $this->modules[$y][$x];
                    $runX = 1;
                }
            }
            $result += $this->finderPenaltyTerminateAndCount($runColor, $runX, $runHistory) * self::PENALTY_N3;
        }
        for ($x = 0; $x < $this->size; $x++) {
            $runColor = false;
            $runY = 0;
            $runHistory = [0, 0, 0, 0, 0, 0, 0];
            for ($y = 0; $y < $this->size; $y++) {
                if ($this->modules[$y][$x] === $runColor) {
                    $runY++;
                    if ($runY === 5) {
                        $result += self::PENALTY_N1;
                    } elseif ($runY > 5) {
                        $result++;
                    }
                } else {
                    $this->finderPenaltyAddHistory($runY, $runHistory);
                    if (!$runColor) {
                        $result += $this->finderPenaltyCountPatterns($runHistory) * self::PENALTY_N3;
                    }
                    $runColor = $this->modules[$y][$x];
                    $runY = 1;
                }
            }
            $result += $this->finderPenaltyTerminateAndCount($runColor, $runY, $runHistory) * self::PENALTY_N3;
        }

        for ($y = 0; $y < $this->size - 1; $y++) {
            for ($x = 0; $x < $this->size - 1; $x++) {
                $color = $this->modules[$y][$x];
                if ($color === $this->modules[$y][$x + 1] && $color === $this->modules[$y + 1][$x] && $color === $this->modules[$y + 1][$x + 1]) {
                    $result += self::PENALTY_N2;
                }
            }
        }

        $dark = 0;
        foreach ($this->modules as $row) {
            foreach ($row as $color) {
                $dark += $color ? 1 : 0;
            }
        }
        $total = $this->size * $this->size;
        $k = (int) ceil(abs($dark * 20 - $total * 10) / $total) - 1;
        $result += $k * self::PENALTY_N4;

        return $result;
    }

    private function getAlignmentPatternPositions(): array
    {
        if ($this->version === 1) {
            return [];
        }
        $numAlign = intdiv($this->version, 7) + 2;
        $step = intdiv($this->version * 8 + $numAlign * 3 + 5, $numAlign * 4 - 4) * 2;
        $result = [6];
        for ($pos = $this->size - 7; count($result) < $numAlign; $pos -= $step) {
            array_splice($result, 1, 0, [$pos]);
        }
        return $result;
    }

    private static function getNumRawDataModules(int $ver): int
    {
        $result = (16 * $ver + 128) * $ver + 64;
        if ($ver >= 2) {
            $numAlign = intdiv($ver, 7) + 2;
            $result -= (25 * $numAlign - 10) * $numAlign - 55;
            if ($ver >= 7) {
                $result -= 36;
            }
        }
        return $result;
    }

    private static function getNumDataCodewords(int $ver, int $ecl): int
    {
        return intdiv(self::getNumRawDataModules($ver), 8)
            - self::ECC_CODEWORDS_PER_BLOCK[$ecl][$ver] * self::NUM_ERROR_CORRECTION_BLOCKS[$ecl][$ver];
    }

    private static function reedSolomonComputeDivisor(int $degree): array
    {
        $result = array_fill(0, $degree - 1, 0);
        $result[] = 1;

        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < count($result); $j++) {
                $result[$j] = self::reedSolomonMultiply($result[$j], $root);
                if ($j + 1 < count($result)) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::reedSolomonMultiply($root, 0x02);
        }
        return $result;
    }

    private static function reedSolomonComputeRemainder(array $data, array $divisor): array
    {
        $result = array_fill(0, count($divisor), 0);
        foreach ($data as $b) {
            $factor = $b ^ array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coef) {
                $result[$i] ^= self::reedSolomonMultiply($coef, $factor);
            }
        }
        return $result;
    }

    private static function reedSolomonMultiply(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }
        return $z;
    }

    private function finderPenaltyCountPatterns(array $runHistory): int
    {
        $n = $runHistory[1];
        $core = $n > 0 && $runHistory[2] === $n && $runHistory[3] === $n * 3 && $runHistory[4] === $n && $runHistory[5] === $n;
        return ($core && $runHistory[0] >= $n * 4 && $runHistory[6] >= $n ? 1 : 0)
            + ($core && $runHistory[6] >= $n * 4 && $runHistory[0] >= $n ? 1 : 0);
    }

    private function finderPenaltyTerminateAndCount(bool $currentRunColor, int $currentRunLength, array &$runHistory): int
    {
        if ($currentRunColor) {
            $this->finderPenaltyAddHistory($currentRunLength, $runHistory);
            $currentRunLength = 0;
        }
        $currentRunLength += $this->size;
        $this->finderPenaltyAddHistory($currentRunLength, $runHistory);
        return $this->finderPenaltyCountPatterns($runHistory);
    }

    private function finderPenaltyAddHistory(int $currentRunLength, array &$runHistory): void
    {
        if ($runHistory[0] === 0) {
            $currentRunLength += $this->size;
        }
        array_pop($runHistory);
        array_unshift($runHistory, $currentRunLength);
    }

    // ---- Segment handling ----

    private static function appendBits(int $val, int $len, array &$bb): void
    {
        for ($i = $len - 1; $i >= 0; $i--) {
            $bb[] = ($val >> $i) & 1;
        }
    }

    private static function getBit(int $x, int $i): bool
    {
        return (($x >> $i) & 1) !== 0;
    }

    private static function numCharCountBits(int $modeBits, int $ver): int
    {
        $table = match ($modeBits) {
            self::MODE_NUMERIC[0] => self::MODE_NUMERIC[1],
            self::MODE_ALPHANUMERIC[0] => self::MODE_ALPHANUMERIC[1],
            default => self::MODE_BYTE[1],
        };
        $index = intdiv($ver + 7, 17);
        return $table[$index];
    }

    /** @return array<int,array{0:int,1:int,2:int[]}> */
    private static function makeSegments(string $text): array
    {
        if ($text === '') {
            return [];
        }
        if (preg_match('/^[0-9]*$/', $text)) {
            return [self::makeNumeric($text)];
        }
        if (preg_match('/^[A-Z0-9 $%*+.\/:-]*$/', $text)) {
            return [self::makeAlphanumeric($text)];
        }
        return [self::makeBytes(array_values(unpack('C*', $text)))];
    }

    private static function makeNumeric(string $digits): array
    {
        $bb = [];
        $len = strlen($digits);
        for ($i = 0; $i < $len;) {
            $n = min($len - $i, 3);
            self::appendBits((int) substr($digits, $i, $n), $n * 3 + 1, $bb);
            $i += $n;
        }
        return [self::MODE_NUMERIC[0], $len, $bb];
    }

    private static function makeAlphanumeric(string $text): array
    {
        $bb = [];
        $len = strlen($text);
        $i = 0;
        for (; $i + 2 <= $len; $i += 2) {
            $temp = strpos(self::ALPHANUMERIC_CHARSET, $text[$i]) * 45;
            $temp += strpos(self::ALPHANUMERIC_CHARSET, $text[$i + 1]);
            self::appendBits($temp, 11, $bb);
        }
        if ($i < $len) {
            self::appendBits(strpos(self::ALPHANUMERIC_CHARSET, $text[$i]), 6, $bb);
        }
        return [self::MODE_ALPHANUMERIC[0], $len, $bb];
    }

    /** @param int[] $data */
    private static function makeBytes(array $data): array
    {
        $bb = [];
        foreach ($data as $b) {
            self::appendBits($b, 8, $bb);
        }
        return [self::MODE_BYTE[0], count($data), $bb];
    }

    /** @param array<int,array{0:int,1:int,2:int[]}> $segs */
    private static function getTotalBits(array $segs, int $version): ?int
    {
        $result = 0;
        foreach ($segs as [$modeBits, $numChars, $bitData]) {
            $ccbits = self::numCharCountBits($modeBits, $version);
            if ($numChars >= (1 << $ccbits)) {
                return null;
            }
            $result += 4 + $ccbits + count($bitData);
        }
        return $result;
    }
}
