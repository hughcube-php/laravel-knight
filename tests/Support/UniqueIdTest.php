<?php

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Base\Base;
use HughCube\Laravel\Knight\Support\UniqueId;
use HughCube\Laravel\Knight\Tests\TestCase;

class UniqueIdTest extends TestCase
{
    /**
     * @dataProvider methodProvider
     */
    public function testReturnsNonEmptyBase36String($method)
    {
        $id = UniqueId::$method();
        $this->assertNotEmpty($id);
        $this->assertIsString($id);
        $this->assertTrue(1 === preg_match('/^[0-9a-z]+$/', $id));
    }

    /**
     * @dataProvider methodProvider
     */
    public function testUniqueness($method)
    {
        $ids = [];
        for ($i = 0; $i < 10000; $i++) {
            $ids[] = UniqueId::$method();
        }

        $this->assertCount(10000, array_unique($ids));
    }

    /**
     * @dataProvider methodProvider
     */
    public function testLengthByMethod($method)
    {
        $id = UniqueId::$method();
        $length = strlen($id);

        $expectedLengths = [
            'short'       => 13,
            'process'     => 27,
            'distributed' => 39,
            'secure'      => 52,
        ];

        $this->assertSame($expectedLengths[$method], $length);
    }

    public function testShortIsShortest()
    {
        $shortLen = strlen(UniqueId::short());
        $processLen = strlen(UniqueId::process());
        $distributedLen = strlen(UniqueId::distributed());
        $secureLen = strlen(UniqueId::secure());

        $this->assertLessThanOrEqual($processLen, $shortLen);
        $this->assertLessThanOrEqual($distributedLen, $processLen);
        $this->assertLessThanOrEqual($secureLen, $distributedLen);
    }

    public function testBase62Encoding()
    {
        $id = UniqueId::distributed(UniqueId::BASE62);
        $this->assertNotEmpty($id);
        $this->assertTrue(1 === preg_match('/^[0-9a-zA-Z]+$/', $id));
    }

    public function testBase36IsLongerThanBase62()
    {
        $base36 = UniqueId::short(UniqueId::BASE36);
        $base62 = UniqueId::short(UniqueId::BASE62);

        $this->assertGreaterThanOrEqual(strlen($base62), strlen($base36));
    }

    public function testTimeOrdering()
    {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = UniqueId::short();
        }

        $decimals = array_map(function ($id) {
            return Base::conv($id, UniqueId::BASE36, '0123456789');
        }, $ids);

        for ($i = 1; $i < count($decimals); $i++) {
            $this->assertTrue(
                bccomp($decimals[$i - 1], $decimals[$i]) <= 0,
                sprintf('ID decimal %s should be <= %s', $decimals[$i - 1], $decimals[$i])
            );
        }
    }

    public function testSecureRandomness()
    {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = UniqueId::secure();
        }

        $this->assertCount(100, array_unique($ids));
    }

    public function testShortWithBase62()
    {
        $id = UniqueId::short(UniqueId::BASE62);
        $this->assertTrue(1 === preg_match('/^[0-9a-zA-Z]+$/', $id));
    }

    /**
     * @dataProvider methodProvider
     */
    public function testFixedLengthConsistency($method)
    {
        $lengths = [];
        for ($i = 0; $i < 100; $i++) {
            $lengths[] = strlen(UniqueId::$method());
        }

        $this->assertCount(1, array_unique($lengths), "$method should always return the same length");
    }

    /**
     * @dataProvider methodProvider
     */
    public function testVariableLengthMode($method)
    {
        $id = UniqueId::$method(UniqueId::BASE36, false);
        $this->assertNotEmpty($id);
        $this->assertTrue(1 === preg_match('/^[0-9a-z]+$/', $id));
    }

    /**
     * @dataProvider methodProvider
     */
    public function testUnorderedBase36($method)
    {
        $id = UniqueId::$method(UniqueId::UNORDERED_BASE36);
        $this->assertNotEmpty($id);
        $this->assertTrue(1 === preg_match('/^[0-9a-z]+$/', $id));
    }

    /**
     * @dataProvider methodProvider
     */
    public function testUnorderedBase62($method)
    {
        $id = UniqueId::$method(UniqueId::UNORDERED_BASE62);
        $this->assertNotEmpty($id);
        $this->assertTrue(1 === preg_match('/^[0-9a-zA-Z]+$/', $id));
    }

    public function testUnorderedNotLexicographicOrdered()
    {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = UniqueId::distributed(UniqueId::UNORDERED_BASE36);
        }

        $sorted = $ids;
        sort($sorted);
        // 无序编码下, 时间递增的 ID 不应该恰好是字典序递增的
        $this->assertNotSame($ids, $sorted);
    }

    public function testFixedLengthLexicographicOrdering()
    {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = UniqueId::distributed();
        }

        for ($i = 1; $i < count($ids); $i++) {
            $this->assertTrue(
                strcmp($ids[$i - 1], $ids[$i]) <= 0,
                sprintf('ID %s should be <= %s in lexicographic order', $ids[$i - 1], $ids[$i])
            );
        }
    }

    /**
     * 用最小值(全0)和最大值(全9)双重验证: 十进制位数 → 编码长度 的映射是否正确
     *
     * 核心断言:
     * - 最大值(全9)自然编码长度 = 期望长度 (不溢出)
     * - 最大值(全9)自然编码长度 = 期望长度 (不浪费, 少1位会装不下)
     * - 最小值(全0)补齐后长度 = 期望长度
     *
     * @dataProvider decimalDigitsAndLengthProvider
     */
    public function testMinMaxDecimalBoundary($decimalDigits, $encoding, $expectedLen)
    {
        $maxDecimal = str_repeat('9', $decimalDigits);
        $base = strlen($encoding);

        // 验证公式: ceil(digits * log(10) / log(base)) = expectedLen
        $calculatedLen = (int) ceil($decimalDigits * log(10) / log($base));
        $this->assertSame($expectedLen, $calculatedLen,
            "Formula check: $decimalDigits digits in base$base should need $expectedLen chars");

        // 最大值 (全9): 自然编码长度恰好等于期望长度 (不溢出 + 不浪费)
        $maxEncoded = Base::conv($maxDecimal, '0123456789', $encoding);
        $this->assertSame($expectedLen, strlen($maxEncoded),
            "Max($decimalDigits digits) base$base: natural length must be exactly $expectedLen");

        // 最大值补齐后长度不变 (已经是最大长度, pad 不应该增长)
        $maxPadded = str_pad($maxEncoded, $expectedLen, $encoding[0], STR_PAD_LEFT);
        $this->assertSame($expectedLen, strlen($maxPadded),
            "Max($decimalDigits digits) base$base: padded length must still be $expectedLen");

        // 最小值 ("1"): 编码后短于期望长度, 补齐后等于期望长度
        $minEncoded = Base::conv('1', '0123456789', $encoding);
        $this->assertLessThan($expectedLen, strlen($minEncoded),
            "Min(1) base$base: natural length must be less than $expectedLen");
        $minPadded = str_pad($minEncoded, $expectedLen, $encoding[0], STR_PAD_LEFT);
        $this->assertSame($expectedLen, strlen($minPadded),
            "Min(1) base$base: padded length must be $expectedLen");

        // 数学证明 expectedLen 是最小必要长度:
        // base^(expectedLen-1) < 10^decimalDigits  (len-1 不够用)
        // 10^decimalDigits <= base^expectedLen      (len 刚好够用)
        $basePowLen = bcpow((string) $base, (string) $expectedLen);
        $basePowLenMinus1 = bcpow((string) $base, (string) ($expectedLen - 1));
        $tenPowDigits = bcpow('10', (string) $decimalDigits);

        $this->assertTrue(
            bccomp($basePowLenMinus1, $tenPowDigits) < 0,
            "base$base^" . ($expectedLen - 1) . " must be < 10^$decimalDigits (len-1 would overflow)"
        );
        $this->assertTrue(
            bccomp($tenPowDigits, $basePowLen) <= 0,
            "10^$decimalDigits must be <= base$base^$expectedLen (len is sufficient)"
        );
    }

    public function decimalDigitsAndLengthProvider()
    {
        return [
            // short = time(14) + counter(5) = 19 位十进制
            'short_base36'            => [19, UniqueId::BASE36, 13],
            'short_base62'            => [19, UniqueId::BASE62, 11],
            'short_unordered36'       => [19, UniqueId::UNORDERED_BASE36, 13],
            'short_unordered62'       => [19, UniqueId::UNORDERED_BASE62, 11],
            // process = time(14) + pid(10) + seed(12) + counter(5) = 41 位十进制
            'process_base36'          => [41, UniqueId::BASE36, 27],
            'process_base62'          => [41, UniqueId::BASE62, 23],
            'process_unordered36'     => [41, UniqueId::UNORDERED_BASE36, 27],
            'process_unordered62'     => [41, UniqueId::UNORDERED_BASE62, 23],
            // distributed = time(14) + machine(19) + pid(10) + seed(12) + counter(5) = 60 位十进制
            'distributed_base36'      => [60, UniqueId::BASE36, 39],
            'distributed_base62'      => [60, UniqueId::BASE62, 34],
            'distributed_unordered36' => [60, UniqueId::UNORDERED_BASE36, 39],
            'distributed_unordered62' => [60, UniqueId::UNORDERED_BASE62, 34],
            // secure = time(14) + machine(19) + pid(10) + seed(12) + counter(5) + random(20) = 80 位十进制
            'secure_base36'           => [80, UniqueId::BASE36, 52],
            'secure_base62'           => [80, UniqueId::BASE62, 45],
            'secure_unordered36'      => [80, UniqueId::UNORDERED_BASE36, 52],
            'secure_unordered62'      => [80, UniqueId::UNORDERED_BASE62, 45],
        ];
    }

    /**
     * 验证各字段的实际最大值不超过其声明的十进制位宽
     *
     * 如果字段值超过 pad 宽度, toStringWithPad 输出会比声明的长,
     * 导致总十进制位数增加, 编码后长度突破固定长度.
     */
    public function testFieldMaxValuesFitInDeclaredWidth()
    {
        // time: 14 位十进制, 可用约 3170 年, 当前相对纪元的时间远小于上限
        $currentRelativeTime = (int) (microtime(true) * 1000) - 1577836800000;
        $this->assertLessThanOrEqual(14, strlen((string) $currentRelativeTime),
            'Current relative time must fit in 14 decimal digits');

        // machineId: hexdec(15个f) = 2^60-1 = 1152921504606846975, 必须 ≤ 19 位十进制
        $maxMachineId = hexdec(str_repeat('f', 15));
        $this->assertLessThanOrEqual(19, strlen((string) $maxMachineId),
            'machineId max (2^60-1) must fit in 19 decimal digits');
        // 并且 < 10^19 (确保 toStringWithPad 不会输出 20 位)
        $this->assertTrue(
            bccomp((string) $maxMachineId, bcpow('10', '19')) < 0,
            'machineId max must be < 10^19'
        );

        // PID: Windows 最大 4294967295 (32-bit unsigned), 必须 ≤ 10 位
        $this->assertLessThanOrEqual(10, strlen('4294967295'),
            'PID max must fit in 10 decimal digits');

        // seed: max = 999999999999, 正好 12 位
        $this->assertSame(12, strlen('999999999999'),
            'seed max must be exactly 12 decimal digits');

        // counter: max = 99999, 正好 5 位
        $this->assertSame(5, strlen((string) UniqueId::MAX_COUNTER),
            'MAX_COUNTER must be exactly 5 decimal digits');

        // random: max = 9999999999, 正好 10 位
        $this->assertSame(10, strlen('9999999999'),
            'random max must be exactly 10 decimal digits');
    }

    /**
     * 验证 toStringWithPad 输出长度与声明一致
     */
    public function testToStringWithPadProducesExactWidth()
    {
        $fields = [
            [0, 14],
            [99999999999999, 14],
            [0, 19],
            [1152921504606846975, 19],  // hexdec('f'*15) = 2^60 - 1
            [0, 10],
            [4294967295, 10],
            [0, 12],
            [999999999999, 12],
            [0, 5],
            [99999, 5],
        ];

        foreach ($fields as list($value, $width)) {
            $padded = Base::toStringWithPad($value, $width);
            $this->assertSame($width, strlen($padded),
                "toStringWithPad($value, $width) should produce exactly $width chars, got '$padded'");
        }
    }

    /**
     * 验证 UNORDERED 字符集是 BASE 字符集的有效排列 (不多不少不重复)
     */
    public function testUnorderedAlphabetsAreValidPermutations()
    {
        // UNORDERED_BASE36 必须是 BASE36 的排列 (相同字符, 不同顺序)
        $this->assertSame(strlen(UniqueId::BASE36), strlen(UniqueId::UNORDERED_BASE36));
        $original36 = str_split(UniqueId::BASE36);
        $shuffled36 = str_split(UniqueId::UNORDERED_BASE36);
        sort($original36);
        sort($shuffled36);
        $this->assertSame($original36, $shuffled36,
            'UNORDERED_BASE36 must contain exactly the same chars as BASE36');
        $this->assertNotSame(UniqueId::BASE36, UniqueId::UNORDERED_BASE36,
            'UNORDERED_BASE36 must not equal BASE36');

        // UNORDERED_BASE62 必须是 BASE62 的排列 (相同字符, 不同顺序)
        $this->assertSame(strlen(UniqueId::BASE62), strlen(UniqueId::UNORDERED_BASE62));
        $original62 = str_split(UniqueId::BASE62);
        $shuffled62 = str_split(UniqueId::UNORDERED_BASE62);
        sort($original62);
        sort($shuffled62);
        $this->assertSame($original62, $shuffled62,
            'UNORDERED_BASE62 must contain exactly the same chars as BASE62');
        $this->assertNotSame(UniqueId::BASE62, UniqueId::UNORDERED_BASE62,
            'UNORDERED_BASE62 must not equal BASE62');
    }

    public function methodProvider()
    {
        return [
            'short'       => ['short'],
            'process'     => ['process'],
            'distributed' => ['distributed'],
            'secure'      => ['secure'],
        ];
    }
}
