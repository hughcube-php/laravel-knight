<?php

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\Str;
use HughCube\Laravel\Knight\Tests\TestCase;

class StrMaskTest extends TestCase
{
    public function testMaskEmail()
    {
        $this->assertSame('u**r@example.com', Str::maskEmail('user@example.com'));
        $this->assertSame('a*@example.com', Str::maskEmail('ab@example.com'));
        $this->assertSame('*@example.com', Str::maskEmail('a@example.com'));
        $this->assertSame('t*************g@example.com', Str::maskEmail('testing1234567g@example.com'));
        $this->assertSame('', Str::maskEmail(''));
        $this->assertSame('', Str::maskEmail(null));
        $this->assertSame('noemail', Str::maskEmail('noemail'));
    }

    public function testMaskBankCard()
    {
        $this->assertSame('6222********1234', Str::maskBankCard('6222123456781234'));
        $this->assertSame('6222*****4321', Str::maskBankCard('6222912344321'));
        $this->assertSame('12345678', Str::maskBankCard('12345678'));
        $this->assertSame('', Str::maskBankCard(''));
        $this->assertSame('', Str::maskBankCard(null));
    }

    public function testMaskName()
    {
        $this->assertSame('张*', Str::maskName('张三'));
        $this->assertSame('张*丰', Str::maskName('张三丰'));
        $this->assertSame('欧**强', Str::maskName('欧阳自强'));
        $this->assertSame('*', Str::maskName('张'));
        $this->assertSame('', Str::maskName(''));
        $this->assertSame('', Str::maskName(null));
    }

    public function testMaskAddress()
    {
        $this->assertSame('北京市海淀区*****', Str::maskAddress('北京市海淀区中关村大街'));
        $this->assertSame('上海市浦东新***********', Str::maskAddress('上海市浦东新区张江高科技园区博云路'));
        $this->assertSame('短地址', Str::maskAddress('短地址'));
        $this->assertSame('', Str::maskAddress(''));
        $this->assertSame('', Str::maskAddress(null));
        $this->assertSame('北京**', Str::maskAddress('北京市海', 2));
    }

    public function testMaskPlateNumber()
    {
        $this->assertSame('京A****8', Str::maskPlateNumber('京A123B8'));
        $this->assertSame('粤B****5', Str::maskPlateNumber('粤B85XS5'));
        $this->assertSame('京A', Str::maskPlateNumber('京A'));
        $this->assertSame('', Str::maskPlateNumber(''));
        $this->assertSame('', Str::maskPlateNumber(null));
        $this->assertSame('京A*5', Str::maskPlateNumber('京A15'));
    }
}
