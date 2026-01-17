<?php

namespace HughCube\Laravel\Knight\Tests\Mixin\Support;

use HughCube\Laravel\Knight\Mixin\Support\StrMixin;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Str;

class StrMixinTest extends TestCase
{
    public function testStringPositionHelpers()
    {
        $mixin = new StrMixin();

        $afterLast = $mixin->afterLast();
        $this->assertSame('bar', $afterLast('foo/bar', '/'));
        $this->assertSame('foo/bar', $afterLast('foo/bar', ''));
        $this->assertSame('foo/bar', $afterLast('foo/bar', '#'));

        $beforeLast = $mixin->beforeLast();
        $this->assertSame('foo', $beforeLast('foo/bar', '/'));
        $this->assertSame('foo/bar', $beforeLast('foo/bar', ''));
        $this->assertSame('foo/bar', $beforeLast('foo/bar', '#'));
    }

    public function testValidationHelpers()
    {
        $mixin = new StrMixin();

        Str::macro('getMobilePattern', $mixin->getMobilePattern());

        $getMobilePattern = $mixin->getMobilePattern();
        $this->assertSame(1, preg_match($getMobilePattern(), '13800138000'));

        $checkMobile = self::callMethod($mixin, 'checkMobile');
        $this->assertTrue($checkMobile('13800138000'));
        $this->assertFalse($checkMobile('abc'));
        $this->assertFalse($checkMobile(null));

        $isUtf8 = self::callMethod($mixin, 'isUtf8');
        $this->assertTrue($isUtf8(null));
        $this->assertTrue($isUtf8('abc'));

        $isOctal = self::callMethod($mixin, 'isOctal');
        $this->assertFalse($isOctal('777'));
        $this->assertTrue($isOctal('78'));

        $isBinary = self::callMethod($mixin, 'isBinary');
        $this->assertFalse($isBinary('1010'));
        $this->assertTrue($isBinary('102'));

        $isHex = self::callMethod($mixin, 'isHex');
        $this->assertFalse($isHex('a1b2'));
        $this->assertTrue($isHex('a1bg'));

        $isAlnum = self::callMethod($mixin, 'isAlnum');
        $this->assertTrue($isAlnum('abc123'));

        $isAlpha = self::callMethod($mixin, 'isAlpha');
        $this->assertTrue($isAlpha('abc'));

        $isNaming = self::callMethod($mixin, 'isNaming');
        $this->assertTrue($isNaming('_name1'));
        $this->assertFalse($isNaming('1name'));

        $isWhitespace = self::callMethod($mixin, 'isWhitespace');
        $this->assertTrue($isWhitespace("\n"));

        $isDigit = self::callMethod($mixin, 'isDigit');
        $this->assertTrue($isDigit('123'));
        $this->assertFalse($isDigit('12.3'));

        $isEmail = self::callMethod($mixin, 'isEmail');
        $this->assertTrue($isEmail('test@example.com'));
        $this->assertFalse($isEmail('not-email'));

        $isTel = self::callMethod($mixin, 'isTel');
        $this->assertTrue($isTel('010-12345678'));
        $this->assertFalse($isTel('1234'));

        $isIp = self::callMethod($mixin, 'isIp');
        $this->assertTrue($isIp('127.0.0.1'));

        $isIp4 = self::callMethod($mixin, 'isIp4');
        $this->assertTrue($isIp4('127.0.0.1'));

        $isIp6 = self::callMethod($mixin, 'isIp6');
        $this->assertTrue($isIp6('::1'));

        $isPrivateIp = self::callMethod($mixin, 'isPrivateIp');
        $this->assertFalse($isPrivateIp('8.8.8.8'));

        $isUrl = self::callMethod($mixin, 'isUrl');
        $this->assertTrue($isUrl('https://example.com'));
        $this->assertFalse($isUrl('not a url'));

        $isPort = self::callMethod($mixin, 'isPort');
        $this->assertTrue($isPort(80));
        $this->assertFalse($isPort(70000));

        $isTrue = self::callMethod($mixin, 'isTrue');
        $this->assertTrue($isTrue(true));
        $this->assertTrue($isTrue('true'));
        $this->assertFalse($isTrue('false'));

        $isChineseName = self::callMethod($mixin, 'isChineseName');
        $this->assertTrue($isChineseName('张三'));

        $hasChinese = self::callMethod($mixin, 'hasChinese');
        $this->assertTrue($hasChinese('abc中文'));

        $isChinese = self::callMethod($mixin, 'isChinese');
        $this->assertTrue($isChinese('中文'));
        $this->assertFalse($isChinese('中文abc'));
    }

    public function testFormattingHelpers()
    {
        $mixin = new StrMixin();

        $maskMobile = self::callMethod($mixin, 'maskMobile');
        $this->assertSame('138****8000', $maskMobile('13800138000'));

        $maskChinaIdCode = self::callMethod($mixin, 'maskChinaIdCode');
        $this->assertSame('110105********002X', $maskChinaIdCode('11010519491231002X'));

        $splitWhitespace = self::callMethod($mixin, 'splitWhitespace');
        $this->assertSame(
            ['a', 'b', 'c'],
            $splitWhitespace("a  b\tc\n", -1, PREG_SPLIT_NO_EMPTY)
        );

        $convEncoding = self::callMethod($mixin, 'convEncoding');
        $this->assertSame('abc', $convEncoding('abc', 'UTF8', 'utf-8'));
        $this->assertSame('abc', $convEncoding('abc', 'gbk', 'utf-8'));
        $this->assertSame('123', $convEncoding(123, 'gbk', 'utf-8'));

        $msubstr = self::callMethod($mixin, 'msubstr');
        $this->assertSame('abcde...', $msubstr('abcdefghijklmnopqrst', 0, 5, '...'));

        $countWords = self::callMethod($mixin, 'countWords');
        $this->assertSame(3, $countWords('one  two three'));

        $offsetGet = self::callMethod($mixin, 'offsetGet');
        $this->assertSame('b', $offsetGet('abc', 1));
        $this->assertSame('', $offsetGet('abc', 5));

        $filterPartialUTF8 = self::callMethod($mixin, 'filterPartialUTF8');
        $this->assertSame('abc', $filterPartialUTF8('abc'));

        $versionCompare = self::callMethod($mixin, 'versionCompare');
        $this->assertSame(-1, $versionCompare('1.2', '1.10'));
        $this->assertTrue((bool) $versionCompare('1.2', '1.2', '>='));

        $mbSplit = self::callMethod($mixin, 'mbSplit');
        $this->assertSame(['ab', 'cd'], $mbSplit('abcd', 2));
    }
}
