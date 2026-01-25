<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ModelTraitExtraTest extends TestCase
{
    // ==================== PostgreSQL Array Helpers Tests ====================

    public function testParsePgArrayEmpty()
    {
        $user = new User();

        $result = self::callMethod($user, 'parsePgArray', ['']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());

        $result = self::callMethod($user, 'parsePgArray', ['{}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());

        $result = self::callMethod($user, 'parsePgArray', ['  {}  ']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testParsePgArrayNumeric()
    {
        $user = new User();

        // 简单整数数组
        $result = self::callMethod($user, 'parsePgArray', ['{1,2,3}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(['1', '2', '3'], $result->all());

        // 带空格
        $result = self::callMethod($user, 'parsePgArray', ['{ 1, 2, 3 }']);
        $this->assertSame(['1', '2', '3'], $result->all());

        // 单个元素
        $result = self::callMethod($user, 'parsePgArray', ['{42}']);
        $this->assertSame(['42'], $result->all());

        // 浮点数
        $result = self::callMethod($user, 'parsePgArray', ['{1.5,2.5,3.5}']);
        $this->assertSame(['1.5', '2.5', '3.5'], $result->all());
    }

    public function testParsePgArrayText()
    {
        $user = new User();

        // 带引号的文本数组
        $result = self::callMethod($user, 'parsePgArray', ['{"a","b","c"}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(['a', 'b', 'c'], $result->all());

        // 混合内容
        $result = self::callMethod($user, 'parsePgArray', ['{"hello","world"}']);
        $this->assertSame(['hello', 'world'], $result->all());

        // 包含空格的文本
        $result = self::callMethod($user, 'parsePgArray', ['{"hello world","foo bar"}']);
        $this->assertSame(['hello world', 'foo bar'], $result->all());
    }

    public function testParsePgArrayWithNull()
    {
        $user = new User();

        // 包含 NULL 值
        $result = self::callMethod($user, 'parsePgArray', ['{1,NULL,3}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(['1', null, '3'], $result->all());

        $result = self::callMethod($user, 'parsePgArray', ['{"a",NULL,"c"}']);
        $this->assertSame(['a', null, 'c'], $result->all());

        // 全部是 NULL
        $result = self::callMethod($user, 'parsePgArray', ['{NULL,NULL}']);
        $this->assertSame([null, null], $result->all());
    }

    public function testParsePgArrayWithEscapedChars()
    {
        $user = new User();

        // 包含转义引号
        $result = self::callMethod($user, 'parsePgArray', ['{"he said \\"hello\\"","world"}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(['he said "hello"', 'world'], $result->all());

        // 包含转义反斜杠
        $result = self::callMethod($user, 'parsePgArray', ['{"path\\\\to\\\\file","other"}']);
        $this->assertSame(['path\\to\\file', 'other'], $result->all());
    }

    public function testFormatPgArrayEmpty()
    {
        $user = new User();

        $this->assertSame('{}', self::callMethod($user, 'formatPgArray', [[]]));
    }

    public function testFormatPgArrayNumeric()
    {
        $user = new User();

        // 整数数组
        $result = self::callMethod($user, 'formatPgArray', [[1, 2, 3]]);
        $this->assertSame('{1,2,3}', $result);

        // 浮点数数组
        $result = self::callMethod($user, 'formatPgArray', [[1.5, 2.5, 3.5]]);
        $this->assertSame('{1.5,2.5,3.5}', $result);
    }

    public function testFormatPgArrayText()
    {
        $user = new User();

        // 简单字符串数组（不含特殊字符，无需引号）
        $result = self::callMethod($user, 'formatPgArray', [['a', 'b', 'c']]);
        $this->assertSame('{a,b,c}', $result);

        // 包含空格（需要引号）
        $result = self::callMethod($user, 'formatPgArray', [['hello world', 'foo bar']]);
        $this->assertSame('{"hello world","foo bar"}', $result);
    }

    public function testFormatPgArrayWithNull()
    {
        $user = new User();

        $result = self::callMethod($user, 'formatPgArray', [[1, null, 3]]);
        $this->assertSame('{1,NULL,3}', $result);

        // 简单字符串无需引号
        $result = self::callMethod($user, 'formatPgArray', [['a', null, 'c']]);
        $this->assertSame('{a,NULL,c}', $result);
    }

    public function testFormatPgArrayWithBoolean()
    {
        $user = new User();

        // 布尔值转换为 PostgreSQL 布尔格式: t/f
        $result = self::callMethod($user, 'formatPgArray', [[true, false, true]]);
        $this->assertSame('{t,f,t}', $result);

        // 单个布尔值
        $this->assertSame('{t}', self::callMethod($user, 'formatPgArray', [[true]]));
        $this->assertSame('{f}', self::callMethod($user, 'formatPgArray', [[false]]));

        // 混合布尔和其他类型
        $result = self::callMethod($user, 'formatPgArray', [[true, 1, false, 0]]);
        $this->assertSame('{t,1,f,0}', $result);
    }

    public function testFormatPgArrayWithSpecialChars()
    {
        $user = new User();

        // 包含引号（需要引号和转义）, 'world' 无需引号
        $result = self::callMethod($user, 'formatPgArray', [['he said "hello"', 'world']]);
        $this->assertSame('{"he said \\"hello\\"",world}', $result);

        // 包含反斜杠（需要引号和转义）, 'other' 无需引号
        $result = self::callMethod($user, 'formatPgArray', [['path\\to\\file', 'other']]);
        $this->assertSame('{"path\\\\to\\\\file",other}', $result);
    }

    public function testParsePgArrayRoundTrip()
    {
        $user = new User();

        // 整数往返
        $original = [1, 2, 3];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertInstanceOf(Collection::class, $parsed);
        $this->assertSame(['1', '2', '3'], $parsed->all());

        // 字符串往返
        $original = ['hello', 'world'];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());

        // 特殊字符往返
        $original = ['he said "hello"', 'path\\to\\file'];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testParsePgArrayCollectionMethods()
    {
        $user = new User();

        // 测试 Collection 方法可用
        $result = self::callMethod($user, 'parsePgArray', ['{1,2,3,4,5}']);
        $this->assertInstanceOf(Collection::class, $result);

        // filter
        $filtered = $result->filter(function ($value) {
            return intval($value) > 2;
        })->values();
        $this->assertSame(['3', '4', '5'], $filtered->all());

        // map
        $mapped = $result->map(function ($value) {
            return intval($value) * 2;
        });
        $this->assertSame([2, 4, 6, 8, 10], $mapped->all());

        // first/last
        $this->assertSame('1', $result->first());
        $this->assertSame('5', $result->last());
    }

    // ==================== formatPgArrayValue 直接测试 ====================

    public function testFormatPgArrayValueWithNull()
    {
        $user = new User();

        $this->assertSame('NULL', self::callMethod($user, 'formatPgArrayValue', [null]));
    }

    public function testFormatPgArrayValueWithBoolean()
    {
        $user = new User();

        $this->assertSame('t', self::callMethod($user, 'formatPgArrayValue', [true]));
        $this->assertSame('f', self::callMethod($user, 'formatPgArrayValue', [false]));
    }

    public function testFormatPgArrayValueWithNumbers()
    {
        $user = new User();

        // 整数
        $this->assertSame('0', self::callMethod($user, 'formatPgArrayValue', [0]));
        $this->assertSame('42', self::callMethod($user, 'formatPgArrayValue', [42]));
        $this->assertSame('-100', self::callMethod($user, 'formatPgArrayValue', [-100]));

        // 浮点数
        $this->assertSame('3.14', self::callMethod($user, 'formatPgArrayValue', [3.14]));
        $this->assertSame('-2.5', self::callMethod($user, 'formatPgArrayValue', [-2.5]));
        // PHP (string)0.0 === '0'
        $this->assertSame('0', self::callMethod($user, 'formatPgArrayValue', [0.0]));
    }

    public function testFormatPgArrayValueWithSimpleStrings()
    {
        $user = new User();

        // 简单字符串不需要引号
        $this->assertSame('hello', self::callMethod($user, 'formatPgArrayValue', ['hello']));
        $this->assertSame('abc123', self::callMethod($user, 'formatPgArrayValue', ['abc123']));
    }

    public function testFormatPgArrayValueWithEmptyString()
    {
        $user = new User();

        // 空字符串必须加引号
        $this->assertSame('""', self::callMethod($user, 'formatPgArrayValue', ['']));
    }

    public function testFormatPgArrayValueWithNullString()
    {
        $user = new User();

        // 字符串 "NULL" 需要引号以区别于真正的 NULL
        $this->assertSame('"NULL"', self::callMethod($user, 'formatPgArrayValue', ['NULL']));
        $this->assertSame('"null"', self::callMethod($user, 'formatPgArrayValue', ['null']));
        $this->assertSame('"Null"', self::callMethod($user, 'formatPgArrayValue', ['Null']));
    }

    public function testFormatPgArrayValueWithSpecialChars()
    {
        $user = new User();

        // 包含空格需要引号
        $this->assertSame('"hello world"', self::callMethod($user, 'formatPgArrayValue', ['hello world']));
        // 包含制表符和换行符（实际字符，不是转义序列）
        $this->assertSame("\"with\ttab\"", self::callMethod($user, 'formatPgArrayValue', ["with\ttab"]));
        $this->assertSame("\"with\nnewline\"", self::callMethod($user, 'formatPgArrayValue', ["with\nnewline"]));

        // 包含逗号需要引号
        $this->assertSame('"a,b,c"', self::callMethod($user, 'formatPgArrayValue', ['a,b,c']));

        // 包含大括号需要引号
        $this->assertSame('"{nested}"', self::callMethod($user, 'formatPgArrayValue', ['{nested}']));
        $this->assertSame('"open{"', self::callMethod($user, 'formatPgArrayValue', ['open{']));
        $this->assertSame('"close}"', self::callMethod($user, 'formatPgArrayValue', ['close}']));
    }

    public function testFormatPgArrayValueWithQuotesAndBackslash()
    {
        $user = new User();

        // 双引号需要转义
        $this->assertSame('"say \\"hello\\""', self::callMethod($user, 'formatPgArrayValue', ['say "hello"']));

        // 反斜杠需要转义
        $this->assertSame('"path\\\\to\\\\file"', self::callMethod($user, 'formatPgArrayValue', ['path\\to\\file']));

        // 组合：引号和反斜杠
        $this->assertSame('"a\\\\\\"b"', self::callMethod($user, 'formatPgArrayValue', ['a\\"b']));
    }

    // ==================== parsePgArrayValue 直接测试 ====================

    public function testParsePgArrayValueWithNull()
    {
        $user = new User();

        // 未加引号的 NULL（不区分大小写）
        $this->assertNull(self::callMethod($user, 'parsePgArrayValue', ['NULL']));
        $this->assertNull(self::callMethod($user, 'parsePgArrayValue', ['null']));
        $this->assertNull(self::callMethod($user, 'parsePgArrayValue', ['Null']));
        $this->assertNull(self::callMethod($user, 'parsePgArrayValue', ['  NULL  ']));
    }

    public function testParsePgArrayValueWithQuotedNull()
    {
        $user = new User();

        // 带引号的 "NULL" 是字符串，不是 null
        $this->assertSame('NULL', self::callMethod($user, 'parsePgArrayValue', ['"NULL"']));
        $this->assertSame('null', self::callMethod($user, 'parsePgArrayValue', ['"null"']));
    }

    public function testParsePgArrayValueWithEmptyQuotedString()
    {
        $user = new User();

        // 空引号字符串
        $this->assertSame('', self::callMethod($user, 'parsePgArrayValue', ['""']));
    }

    public function testParsePgArrayValueWithEscapedQuotes()
    {
        $user = new User();

        // 转义的双引号
        $this->assertSame('say "hello"', self::callMethod($user, 'parsePgArrayValue', ['"say \\"hello\\""']));
        $this->assertSame('"', self::callMethod($user, 'parsePgArrayValue', ['"\\""']));
        $this->assertSame('""', self::callMethod($user, 'parsePgArrayValue', ['"\\"\\""']));
    }

    public function testParsePgArrayValueWithEscapedBackslash()
    {
        $user = new User();

        // 转义的反斜杠
        $this->assertSame('path\\to\\file', self::callMethod($user, 'parsePgArrayValue', ['"path\\\\to\\\\file"']));
        $this->assertSame('\\', self::callMethod($user, 'parsePgArrayValue', ['"\\\\"']));
        $this->assertSame('\\\\', self::callMethod($user, 'parsePgArrayValue', ['"\\\\\\\\"']));
    }

    public function testParsePgArrayValueWithComplexEscapeSequences()
    {
        $user = new User();

        // 反斜杠后跟引号: \" -> "
        $this->assertSame('"', self::callMethod($user, 'parsePgArrayValue', ['"\\""']));

        // 两个反斜杠后跟引号: \\" -> \" (一个反斜杠 + 一个引号)
        $this->assertSame('\\"', self::callMethod($user, 'parsePgArrayValue', ['"\\\\\\"" ']));

        // 三个反斜杠后跟引号: \\\" -> \\" (但这不太可能在实际中出现)
        $this->assertSame('\\"', self::callMethod($user, 'parsePgArrayValue', ['"\\\\\\""']));

        // 末尾单个反斜杠
        $this->assertSame('end\\', self::callMethod($user, 'parsePgArrayValue', ['"end\\\\"']));
    }

    // ==================== parsePgArray 边界情况测试 ====================

    public function testParsePgArrayWithEmptyStrings()
    {
        $user = new User();

        // 数组中包含空字符串
        $result = self::callMethod($user, 'parsePgArray', ['{"","a",""}']);
        $this->assertSame(['', 'a', ''], $result->all());

        // 只有空字符串
        $result = self::callMethod($user, 'parsePgArray', ['{"","",""}']);
        $this->assertSame(['', '', ''], $result->all());
    }

    public function testParsePgArrayWithQuotedNull()
    {
        $user = new User();

        // 带引号的 NULL 应该作为字符串解析
        $result = self::callMethod($user, 'parsePgArray', ['{"NULL",NULL,"null"}']);
        $this->assertSame(['NULL', null, 'null'], $result->all());
    }

    public function testParsePgArrayWithMixedTypes()
    {
        $user = new User();

        // 混合各种类型
        $result = self::callMethod($user, 'parsePgArray', ['{1,"hello",NULL,3.14,""}']);
        $this->assertSame(['1', 'hello', null, '3.14', ''], $result->all());
    }

    public function testParsePgArrayWithConsecutiveEscapes()
    {
        $user = new User();

        // 连续反斜杠
        $result = self::callMethod($user, 'parsePgArray', ['{"\\\\\\\\"}']);
        $this->assertSame(['\\\\'], $result->all());

        // 反斜杠后跟引号
        $result = self::callMethod($user, 'parsePgArray', ['{"a\\"b"}']);
        $this->assertSame(['a"b'], $result->all());

        // 复杂组合
        $result = self::callMethod($user, 'parsePgArray', ['{"a\\\\b\\"c"}']);
        $this->assertSame(['a\\b"c'], $result->all());
    }

    public function testParsePgArrayWithSpecialCharsInValues()
    {
        $user = new User();

        // 包含逗号
        $result = self::callMethod($user, 'parsePgArray', ['{"a,b","c,d,e"}']);
        $this->assertSame(['a,b', 'c,d,e'], $result->all());

        // 包含大括号
        $result = self::callMethod($user, 'parsePgArray', ['{"{nested}","open{","close}"}']);
        $this->assertSame(['{nested}', 'open{', 'close}'], $result->all());

        // 包含空格
        $result = self::callMethod($user, 'parsePgArray', ['{"  leading","trailing  "," both "}']);
        $this->assertSame(['  leading', 'trailing  ', ' both '], $result->all());
    }

    public function testParsePgArrayWithUnicodeChars()
    {
        $user = new User();

        // 中文字符
        $result = self::callMethod($user, 'parsePgArray', ['{"你好","世界"}']);
        $this->assertSame(['你好', '世界'], $result->all());

        // Emoji
        $result = self::callMethod($user, 'parsePgArray', ['{"😀","🎉","🚀"}']);
        $this->assertSame(['😀', '🎉', '🚀'], $result->all());

        // 混合 Unicode
        $result = self::callMethod($user, 'parsePgArray', ['{"日本語","한국어","العربية"}']);
        $this->assertSame(['日本語', '한국어', 'العربية'], $result->all());
    }

    public function testParsePgArrayWithUnquotedSimpleValues()
    {
        $user = new User();

        // PostgreSQL 允许简单值不加引号
        $result = self::callMethod($user, 'parsePgArray', ['{abc,def,ghi}']);
        $this->assertSame(['abc', 'def', 'ghi'], $result->all());

        // 混合引号和非引号
        $result = self::callMethod($user, 'parsePgArray', ['{abc,"with space",xyz}']);
        $this->assertSame(['abc', 'with space', 'xyz'], $result->all());
    }

    // ==================== formatPgArray 边界情况测试 ====================

    public function testFormatPgArrayWithEmptyStrings()
    {
        $user = new User();

        // 包含空字符串（空字符串需要引号，简单字符串 'a' 不需要）
        $result = self::callMethod($user, 'formatPgArray', [['', 'a', '']]);
        $this->assertSame('{"",a,""}', $result);
    }

    public function testFormatPgArrayWithNullString()
    {
        $user = new User();

        // 字符串 NULL 需要引号
        $result = self::callMethod($user, 'formatPgArray', [['NULL', null, 'null']]);
        $this->assertSame('{"NULL",NULL,"null"}', $result);
    }

    public function testFormatPgArrayWithMixedTypes()
    {
        $user = new User();

        // 混合类型（'hello' 是简单字符串无需引号）
        $result = self::callMethod($user, 'formatPgArray', [[1, 'hello', null, 3.14, '', true]]);
        $this->assertSame('{1,hello,NULL,3.14,"",t}', $result);
    }

    public function testFormatPgArrayWithUnicodeChars()
    {
        $user = new User();

        // Unicode 字符（无特殊字符，不需要引号）
        $result = self::callMethod($user, 'formatPgArray', [['你好', '世界']]);
        $this->assertSame('{你好,世界}', $result);

        $result = self::callMethod($user, 'formatPgArray', [['😀', '🎉']]);
        $this->assertSame('{😀,🎉}', $result);
    }

    // ==================== 往返测试 (Round-Trip) ====================

    public function testRoundTripWithEmptyStrings()
    {
        $user = new User();

        $original = ['', 'middle', ''];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testRoundTripWithNullValues()
    {
        $user = new User();

        $original = ['a', null, 'b', null];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testRoundTripWithNullString()
    {
        $user = new User();

        $original = ['NULL', 'null', 'Null'];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testRoundTripWithQuotesAndBackslashes()
    {
        $user = new User();

        $original = ['say "hello"', 'path\\to\\file', 'quote\\"escape'];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testRoundTripWithConsecutiveBackslashes()
    {
        $user = new User();

        $original = ['\\', '\\\\', '\\\\\\'];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testRoundTripWithAllSpecialChars()
    {
        $user = new User();

        $original = [
            '{curly}',
            'comma,here',
            '"double"',
            'back\\slash',
            ' spaces ',
            "tab\there",
            '',
            'NULL',
        ];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testRoundTripWithUnicode()
    {
        $user = new User();

        $original = ['你好世界', '日本語テスト', '한글 테스트', '😀🎉🚀'];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testRoundTripWithComplexMixedContent()
    {
        $user = new User();

        $original = [
            'simple',
            'with space',
            'with,comma',
            'with"quote',
            'with\\backslash',
            'with\\"both',
            '',
            'NULL',
            'unicode你好',
            '{braces}',
        ];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testParsePgArrayEdgeCasesComprehensive()
    {
        $user = new User();

        $testCases = [
            // [input, expected]
            ['{}', []],
            ['', []],
            ['{1}', ['1']],
            ['{"a"}', ['a']],
            ['{NULL}', [null]],
            ['{""}', ['']],
            ['{a,b,c}', ['a', 'b', 'c']],
            ['{"a","b","c"}', ['a', 'b', 'c']],
            ['{a,"b c",d}', ['a', 'b c', 'd']],
            ['  { 1 , 2 , 3 }  ', ['1', '2', '3']],
            ['{"say \\"hi\\""}', ['say "hi"']],
            ['{"c:\\\\path"}', ['c:\\path']],
            ['{NULL,NULL,NULL}', [null, null, null]],
            ['{1,NULL,2}', ['1', null, '2']],
            ['{"NULL"}', ['NULL']],
        ];

        foreach ($testCases as $index => $case) {
            [$input, $expected] = $case;
            $result = self::callMethod($user, 'parsePgArray', [$input]);
            $this->assertSame($expected, $result->all(), "Case #{$index}: parsePgArray('{$input}')");
        }
    }

    public function testFormatPgArrayEdgeCasesComprehensive()
    {
        $user = new User();

        $testCases = [
            // [input, expected]
            [[], '{}'],
            [[1], '{1}'],
            [[3.14], '{3.14}'],
            [['a'], '{a}'],  // 简单字符串无需引号
            [[null], '{NULL}'],
            [[true], '{t}'],
            [[false], '{f}'],
            [[''], '{""}'],
            [[1, 2, 3], '{1,2,3}'],
            [['a', 'b'], '{a,b}'],  // 简单字符串无需引号
            [[1, null, 2], '{1,NULL,2}'],
            [[true, false], '{t,f}'],
            [['hello world'], '{"hello world"}'],
            [['a,b'], '{"a,b"}'],
            [['say "hi"'], '{"say \\"hi\\""}'],
            [['c:\\path'], '{"c:\\\\path"}'],
            [['NULL'], '{"NULL"}'],
        ];

        foreach ($testCases as $index => $case) {
            [$input, $expected] = $case;
            $result = self::callMethod($user, 'formatPgArray', [$input]);
            $this->assertSame($expected, $result, "Case #{$index}: formatPgArray(" . json_encode($input) . ")");
        }
    }

    // ==================== Existing Tests ====================

    public function testCacheHelpers()
    {
        $user = new User();

        $placeholder = $user->getCachePlaceholder();
        $this->assertNotNull($placeholder);
        $this->assertTrue($user->hasCachePlaceholder());
        $this->assertTrue($user->isCachePlaceholder($placeholder));
        $this->assertFalse($user->isCachePlaceholder($placeholder.'-other'));

        $this->assertSame('m1', $user->getModelCachePrefix());
        $this->assertSame('v1', $user->getCacheVersion());
        $this->assertSame(10, $user->getCacheTtl());

        $this->assertFalse($user->isFromCache());
        $this->assertSame($user, $user->setIsFromCache());
        $this->assertTrue($user->isFromCache());
        $this->assertSame($user, $user->setIsFromCache(false));
        $this->assertFalse($user->isFromCache());

        $user->id = 1;
        $user->nickname = 'neo';
        $this->assertSame(
            [
                ['id' => 1],
                ['nickname' => 'neo'],
            ],
            $user->onChangeRefreshCacheKeys()
        );
    }

    public function testGetSetColumnCollection()
    {
        $user = new User();
        $user->tags = 'admin,editor,,admin,';

        $collection = $user->getSetColumnCollection('tags');
        $this->assertSame(['admin', 'editor'], $collection->all());

        $filtered = $user->getSetColumnCollection('tags', ',', function ($value) {
            return $value !== '' && $value !== 'editor';
        });
        $this->assertSame(['admin'], $filtered->all());

        $user->roles = 'a|b|a';
        $this->assertSame(['a', 'b'], $user->getSetColumnCollection('roles', '|')->all());
    }

    public function testVersionAndSortHelpers()
    {
        $now = Carbon::create(2025, 1, 1, 0, 0, 0);
        Carbon::setTestNow($now);

        try {
            $user = new User();
            $this->assertSame($now->getTimestamp() - 1660899108, $user->genDefaultSort());
        } finally {
            Carbon::setTestNow();
        }

        $version = User::genModelVersion();
        $this->assertIsInt($version);
        $this->assertGreaterThanOrEqual(0, $version);

        $user = new User();
        $user->resetModelVersion();
        $this->assertIsInt($user->data_version);
        $this->assertGreaterThanOrEqual(0, $user->data_version);
    }

    public function testMakeColumnsCacheKeyIsDeterministic()
    {
        $user = new User();

        $keyId = $user->makeColumnsCacheKey(['id' => 5]);
        $keyNumeric = $user->makeColumnsCacheKey([5]);
        $this->assertSame($keyId, $keyNumeric);

        $this->assertNotSame($keyId, $user->makeColumnsCacheKey(['id' => 6]));

        $keyOrderA = $user->makeColumnsCacheKey(['id' => 5, 'nickname' => 'neo']);
        $keyOrderB = $user->makeColumnsCacheKey(['nickname' => 'neo', 'id' => 5]);
        $this->assertSame($keyOrderA, $keyOrderB);
    }

    public function testJson2ArrayAndEqualityHelpers()
    {
        $user = new User();

        $this->assertSame([], self::callMethod($user, 'json2Array', ['', false]));
        $this->assertSame(['a', '', 'b'], self::callMethod($user, 'json2Array', ['["a","", "b"]', false]));
        $filtered = self::callMethod($user, 'json2Array', ['["a","", "b"]', true]);
        $this->assertSame(['a', 'b'], array_values($filtered));

        $filtered = self::callMethod($user, 'json2Array', ['["a","bb","ccc"]', function ($value) {
            return strlen($value) > 1;
        }]);
        $this->assertSame(['bb', 'ccc'], array_values($filtered));

        $userA = new User();
        $userA->setRawAttributes(['id' => 1, 'nickname' => 'neo']);

        $userB = new User();
        $userB->setRawAttributes(['id' => 1, 'nickname' => 'neo']);
        $this->assertTrue($userA->equal($userB));

        $userB->setRawAttributes(['id' => 1, 'nickname' => 'trinity']);
        $this->assertFalse($userA->equal($userB));

        $userC = new User();
        $userC->setRawAttributes(['id' => 1]);
        $this->assertFalse($userA->equal($userC));

        $this->assertSame($userA, $userA->ifReturnSelf(true));
        $this->assertNull($userA->ifReturnSelf(false));

        $deleted = new User();
        $deleted->deleted_at = Carbon::now();
        $this->assertNull($deleted->ifAvailableReturnSelf());
        $this->assertTrue(User::isAvailableModel($userA));
        $this->assertFalse(User::isAvailableModel($deleted));
    }
}
