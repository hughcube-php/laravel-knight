<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 19:11.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\Str;
use HughCube\Laravel\Knight\Tests\TestCase;

class StrTest extends TestCase
{
    public function testCountCommonChars()
    {
        $this->assertSame(Str::countCommonChars('我喜欢编程', '编程让我快乐'), 3);
        $this->assertSame(Str::countCommonChars('我喜欢编程', '编a程让我快乐'), 3);
        $this->assertSame(Str::countCommonChars('a喜欢编程', '编a程让我快乐'), 3);

        $this->assertSame(Str::countCommonChars('我喜欢编程', '编程让我快乐', true), 1);
        $this->assertSame(Str::countCommonChars('我喜欢编程', '程编让我快乐', true), 1);
        $this->assertSame(Str::countCommonChars('a喜欢编程我', '编a程让我快乐', true), 3);
    }

    public function testBase64Url()
    {
        for ($i = 1; $i <= 100; $i++) {
            $data = random_bytes($i * 100);

            $string = Str::base64UrlEncode($data);
            $this->assertSame($data, Str::base64UrlDecode($string));
        }
    }

    public function testIsChinese()
    {
        $this->assertTrue(!Str::isChinese('a'));
        $this->assertTrue(!Str::isChinese('张三a'));

        $this->assertTrue(Str::isChinese('张三'));
        $this->assertTrue(Str::isChinese('犇猋骉麤毳淼焱垚昍琰'));
    }

    public function testHasChinese()
    {
        $this->assertTrue(!Str::hasChinese('a'));
        $this->assertTrue(!Str::hasChinese('abcd1234ddg&)129'));

        $this->assertTrue(Str::hasChinese('张三a'));

        $this->assertTrue(Str::hasChinese('张三'));
        $this->assertTrue(Str::hasChinese('犇猋骉麤毳淼焱垚昍琰'));
    }

    public function testIsChineseName()
    {
        $this->assertTrue(!Str::isChineseName('a'));
        $this->assertTrue(!Str::isChineseName('abcd1234ddg&)129'));

        $this->assertTrue(!Str::isChineseName('张三a'));

        $this->assertTrue(!Str::isChineseName('张'));

        $this->assertTrue(Str::isChineseName('张三'));
        $this->assertTrue(Str::isChineseName('犇猋骉麤毳淼焱垚昍琰'));

        $this->assertTrue(!Str::isChineseName('·犇猋骉麤毳淼焱垚昍琰'));
        $this->assertTrue(Str::isChineseName('犇猋骉麤毳·淼焱垚昍琰'));
        $this->assertTrue(!Str::isChineseName('犇猋骉麤毳淼焱垚昍琰·'));
    }
}
