<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 19:11.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Carbon;

class CarbonTest extends TestCase
{
    public function testGetTimestampAsFloat()
    {
        $this->assertIsFloat(Carbon::now()->getTimestampAsFloat());
    }

    public function testGetTimestampAsString()
    {
        $now = Carbon::now();
        $this->assertIsNumeric($now->getTimestampAsString());
    }

    public function testToChineseDate()
    {
        $date = Carbon::parse('2023-07-31');
        $this->assertSame($date->toChineseDate(), '二〇二三年七月三十一日');

        $date = Carbon::parse('2000-12-30');
        $this->assertSame($date->toChineseDate(), '二〇〇〇年十二月三十日');

        $date = Carbon::parse('2001-10-01');
        $this->assertSame($date->toChineseDate(), '二〇〇一年十月一日');
    }
}
