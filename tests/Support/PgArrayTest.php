<?php

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\PgArray;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Collection;

class PgArrayTest extends TestCase
{
    // ==================== parseIntArray Tests ====================

    public function testParseIntArrayWithValidData()
    {
        $result = PgArray::parseIntArray('{1,2,3}');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals([1, 2, 3], $result->all());
    }

    public function testParseIntArrayWithSingleElement()
    {
        $result = PgArray::parseIntArray('{42}');
        $this->assertEquals([42], $result->all());
    }

    public function testParseIntArrayWithNegativeNumbers()
    {
        $result = PgArray::parseIntArray('{-1,-2,3}');
        $this->assertEquals([-1, -2, 3], $result->all());
    }

    public function testParseIntArrayWithEmptyString()
    {
        $result = PgArray::parseIntArray('');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testParseIntArrayWithEmptyArray()
    {
        $result = PgArray::parseIntArray('{}');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testParseIntArrayWithNull()
    {
        $result = PgArray::parseIntArray(null);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testParseIntArrayWithNonString()
    {
        $result = PgArray::parseIntArray(123);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testParseIntArrayWithLargeNumbers()
    {
        $result = PgArray::parseIntArray('{1000000,2000000,3000000}');
        $this->assertEquals([1000000, 2000000, 3000000], $result->all());
    }

    public function testParseIntArrayWithZero()
    {
        $result = PgArray::parseIntArray('{0,1,2}');
        $this->assertEquals([0, 1, 2], $result->all());
    }

    public function testParseIntArrayWithBigInt()
    {
        $phpIntMax = (string) PHP_INT_MAX;
        $phpIntMin = (string) PHP_INT_MIN;

        $result = PgArray::parseIntArray(sprintf('{%s,%s}', $phpIntMax, $phpIntMin));
        $this->assertSame(PHP_INT_MAX, $result[0]);
        $this->assertSame(PHP_INT_MIN, $result[1]);
    }

    public function testParseIntArrayWithBigIntOverflow()
    {
        $overflowValue = '9223372036854775808'; // PHP_INT_MAX + 1
        $underflowValue = '-9223372036854775809'; // PHP_INT_MIN - 1

        $result = PgArray::parseIntArray(sprintf('{%s,%s,123}', $overflowValue, $underflowValue));

        $this->assertSame($overflowValue, $result[0]);
        $this->assertSame($underflowValue, $result[1]);
        $this->assertSame(123, $result[2]);
    }

    public function testParseIntArrayWithWhitespace()
    {
        $result = PgArray::parseIntArray('{ 1, 2, 3 }');
        $this->assertInstanceOf(Collection::class, $result);
    }

    // ==================== parseSimpleTextArray Tests ====================

    public function testParseSimpleTextArrayWithValidData()
    {
        $result = PgArray::parseSimpleTextArray('{o:1,o:2,o:3}');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(['o:1', 'o:2', 'o:3'], $result->all());
    }

    public function testParseSimpleTextArrayWithSingleElement()
    {
        $result = PgArray::parseSimpleTextArray('{hello}');
        $this->assertEquals(['hello'], $result->all());
    }

    public function testParseSimpleTextArrayWithMixedContent()
    {
        $result = PgArray::parseSimpleTextArray('{a-b,c_d,e.f,g:h}');
        $this->assertEquals(['a-b', 'c_d', 'e.f', 'g:h'], $result->all());
    }

    public function testParseSimpleTextArrayWithEmptyString()
    {
        $result = PgArray::parseSimpleTextArray('');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testParseSimpleTextArrayWithEmptyArray()
    {
        $result = PgArray::parseSimpleTextArray('{}');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testParseSimpleTextArrayWithNull()
    {
        $result = PgArray::parseSimpleTextArray(null);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testParseSimpleTextArrayWithNonString()
    {
        $result = PgArray::parseSimpleTextArray(123);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    // ==================== serializeIntArray Tests ====================

    public function testSerializeIntArrayWithValidData()
    {
        $this->assertEquals('{1,2,3}', PgArray::serializeIntArray([1, 2, 3]));
    }

    public function testSerializeIntArrayWithSingleElement()
    {
        $this->assertEquals('{42}', PgArray::serializeIntArray([42]));
    }

    public function testSerializeIntArrayWithEmptyArray()
    {
        $this->assertEquals('{}', PgArray::serializeIntArray([]));
    }

    public function testSerializeIntArrayWithNegativeNumbers()
    {
        $result = PgArray::serializeIntArray([-1, 2, -3]);
        $this->assertStringStartsWith('{', $result);
        $this->assertStringEndsWith('}', $result);
    }

    public function testSerializeIntArrayWithStringNumbers()
    {
        $this->assertEquals('{1,2,3}', PgArray::serializeIntArray(['1', '2', '3']));
    }

    public function testSerializeIntArrayFiltersNonDigit()
    {
        $this->assertEquals('{1,2,3}', PgArray::serializeIntArray([1, 'abc', 2, null, 3]));
    }

    public function testSerializeIntArrayWithZero()
    {
        $this->assertEquals('{0,1,2}', PgArray::serializeIntArray([0, 1, 2]));
    }

    public function testSerializeIntArrayWithCollection()
    {
        $this->assertEquals('{1,2,3}', PgArray::serializeIntArray(collect([1, 2, 3])));
    }

    public function testSerializeIntArrayWithBigInt()
    {
        $bigints = ['9223372036854775807', '-9223372036854775808', '9223372036854775808'];
        $this->assertEquals('{9223372036854775807,-9223372036854775808,9223372036854775808}', PgArray::serializeIntArray($bigints));
    }

    public function testSerializeIntArrayWithDuplicates()
    {
        $this->assertEquals('{1,1,2,2,3,3}', PgArray::serializeIntArray([1, 1, 2, 2, 3, 3]));
    }

    // ==================== serializeSimpleTextArray Tests ====================

    public function testSerializeSimpleTextArrayWithValidData()
    {
        $this->assertEquals('{o:1,o:2,o:3}', PgArray::serializeSimpleTextArray(['o:1', 'o:2', 'o:3']));
    }

    public function testSerializeSimpleTextArrayWithSingleElement()
    {
        $this->assertEquals('{hello}', PgArray::serializeSimpleTextArray(['hello']));
    }

    public function testSerializeSimpleTextArrayWithEmptyArray()
    {
        $this->assertEquals('{}', PgArray::serializeSimpleTextArray([]));
    }

    public function testSerializeSimpleTextArrayFiltersEmptyStrings()
    {
        $this->assertEquals('{a,b,c}', PgArray::serializeSimpleTextArray(['a', '', 'b', '', 'c']));
    }

    public function testSerializeSimpleTextArrayFiltersInvalidCharacters()
    {
        $this->assertEquals('{valid,also-valid}', PgArray::serializeSimpleTextArray(['valid', 'also-valid', 'has space']));
    }

    public function testSerializeSimpleTextArrayAllowsValidCharacters()
    {
        $this->assertEquals('{aZ09,a:b,a_b,a.b,a-b}', PgArray::serializeSimpleTextArray(['aZ09', 'a:b', 'a_b', 'a.b', 'a-b']));
    }

    public function testSerializeSimpleTextArrayAllowsExtendedCharacters()
    {
        $this->assertEquals('{a/b,user@domain,a+b,key=val,tag#1,path~home}', PgArray::serializeSimpleTextArray(['a/b', 'user@domain', 'a+b', 'key=val', 'tag#1', 'path~home']));
    }

    public function testSerializeSimpleTextArrayAllAllowedCharacters()
    {
        $allowedCases = [
            'a', 'Z', '5', 'a:b', 'a_b', 'a.b', 'a/b', 'a-b',
            'a@b', 'a+b', 'a=b', 'a#b', 'a~b',
            ':', '_', '.', '/', '-', '@', '+', '=', '#', '~',
            ':_./-@+=#~',
        ];

        foreach ($allowedCases as $input) {
            $result = PgArray::serializeSimpleTextArray([$input]);
            $this->assertEquals(sprintf('{%s}', $input), $result, "Character '$input' should be allowed");
        }
    }

    public function testSerializeSimpleTextArrayAllDisallowedCharacters()
    {
        $disallowedCases = [
            'a b', 'a,b', 'a{b', 'a}b', 'a"b', "a'b", 'a\\b',
            'a;b', 'a*b', 'a?b', 'a&b', 'a|b', 'a<b', 'a>b',
            'a`b', 'a$b', 'a%b', 'a^b', 'a(b', 'a)b', 'a[b', 'a]b',
            "a\nb", "a\tb",
        ];

        foreach ($disallowedCases as $input) {
            $result = PgArray::serializeSimpleTextArray([$input]);
            $this->assertEquals('{}', $result, "Character '$input' should be filtered out");
        }
    }

    public function testSerializeSimpleTextArrayFiltersDisallowedCharacters()
    {
        $result = PgArray::serializeSimpleTextArray(
            ['valid', 'has space', 'has,comma', 'has{brace', 'has}brace', 'has"quote', "has'quote", 'has\\slash']
        );
        $this->assertEquals('{valid}', $result);
    }

    public function testSerializeSimpleTextArrayWithPathLikeStrings()
    {
        $this->assertEquals('{path/to/file,dir/subdir,a.b/c.d}', PgArray::serializeSimpleTextArray(['path/to/file', 'dir/subdir', 'a.b/c.d']));
    }

    public function testSerializeSimpleTextArrayWithEmailLikeStrings()
    {
        $this->assertEquals('{user@example.com,test+tag@domain.org}', PgArray::serializeSimpleTextArray(['user@example.com', 'test+tag@domain.org']));
    }

    public function testSerializeSimpleTextArrayWithCollection()
    {
        $this->assertEquals('{a,b,c}', PgArray::serializeSimpleTextArray(collect(['a', 'b', 'c'])));
    }

    public function testSerializeSimpleTextArrayFiltersNonStrings()
    {
        $this->assertEquals('{valid,also-valid}', PgArray::serializeSimpleTextArray(['valid', 123, 'also-valid', null]));
    }

    public function testSerializeSimpleTextArrayWithDuplicates()
    {
        $this->assertEquals('{a,a,b,b}', PgArray::serializeSimpleTextArray(['a', 'a', 'b', 'b']));
    }

    // ==================== isIntegerInPhpRange Tests ====================

    public function testIsIntegerInPhpRange()
    {
        $phpIntMax = \HughCube\Base\Base::toString(PHP_INT_MAX);
        $phpIntMin = \HughCube\Base\Base::toString(PHP_INT_MIN);

        // 基本值测试
        $this->assertTrue(PgArray::isIntegerInPhpRange('0'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('1'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('-1'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('123456789'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('-123456789'));

        // 精确边界测试
        $this->assertTrue(PgArray::isIntegerInPhpRange($phpIntMax));
        $this->assertTrue(PgArray::isIntegerInPhpRange($phpIntMin));

        // 边界 +1/-1 测试
        $overMax = function_exists('bcadd') ? bcadd($phpIntMax, '1', 0) : '9223372036854775808';
        $underMin = function_exists('bcsub') ? bcsub($phpIntMin, '1', 0) : '-9223372036854775809';
        $this->assertFalse(PgArray::isIntegerInPhpRange($overMax));
        $this->assertFalse(PgArray::isIntegerInPhpRange($underMin));

        // 大数测试
        $this->assertFalse(PgArray::isIntegerInPhpRange('99999999999999999999'));
        $this->assertFalse(PgArray::isIntegerInPhpRange('-99999999999999999999'));

        // 位数较少的值
        $this->assertTrue(PgArray::isIntegerInPhpRange('999999999999999999'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('-999999999999999999'));

        // 前导零和正号测试
        $this->assertTrue(PgArray::isIntegerInPhpRange('0000123'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('+123'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('+0'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('-0'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('00000000000000000000'));

        // 19位但在范围内
        $this->assertTrue(PgArray::isIntegerInPhpRange('1000000000000000000'));
        $this->assertTrue(PgArray::isIntegerInPhpRange('-1000000000000000000'));

        // 19位但超出范围
        $this->assertFalse(PgArray::isIntegerInPhpRange('9999999999999999999'));
        $this->assertFalse(PgArray::isIntegerInPhpRange('-9999999999999999999'));

        // 边界值减1
        $maxMinus1 = function_exists('bcsub') ? bcsub($phpIntMax, '1', 0) : '9223372036854775806';
        $minPlus1 = function_exists('bcadd') ? bcadd($phpIntMin, '1', 0) : '-9223372036854775807';
        $this->assertTrue(PgArray::isIntegerInPhpRange($maxMinus1));
        $this->assertTrue(PgArray::isIntegerInPhpRange($minPlus1));
    }

    // ==================== Round-trip Tests ====================

    public function testIntArrayRoundTrip()
    {
        $original = [1, 2, 3, 4, 5];
        $serialized = PgArray::serializeIntArray($original);
        $parsed = PgArray::parseIntArray($serialized);
        $this->assertEquals($original, $parsed->all());
    }

    public function testSimpleTextArrayRoundTrip()
    {
        $original = ['tag:1', 'tag:2', 'tag:3'];
        $serialized = PgArray::serializeSimpleTextArray($original);
        $parsed = PgArray::parseSimpleTextArray($serialized);
        $this->assertEquals($original, $parsed->all());
    }
}
