<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/2
 * Time: 21:34.
 */

namespace HughCube\Laravel\Knight\Tests\Mixin\Support;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Str;

class StrMixin extends TestCase
{
    public function testMaskMobile()
    {
        $this->assertSame('188****8888', Str::maskMobile('18888888888'));
    }

    public function testMaskChinaIdCode()
    {
        $this->assertSame('110225********6127', Str::maskChinaIdCode('110225196403026127'));
    }

    public function testMbSplit()
    {
        $this->assertSame(['一', '个', '萝', '卜', '1', '个', 'k', 'e', 'n', 'g'], Str::mbSplit('一个萝卜1个keng'));

        $this->assertSame(['2个', '萝卜', '2个', 'ke', 'ng', ','], Str::mbSplit('2个萝卜2个keng,', 2));
    }
}
