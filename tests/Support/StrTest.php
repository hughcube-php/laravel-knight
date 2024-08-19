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
use Illuminate\Support\Carbon;

class StrTest extends TestCase
{
    public function testCountCommonChars()
    {
        $this->assertSame(Str::countCommonChars('我喜欢编程', '编程让我快乐'), 3);
        $this->assertSame(Str::countCommonChars('我喜欢编程', '编a程让我快乐'), 3);
        $this->assertSame(Str::countCommonChars('a喜欢编程', '编a程让我快乐'), 3);
    }
}
