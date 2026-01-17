<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 19:11.
 */

namespace HughCube\Laravel\Knight\Tests\Mixin\Support;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Carbon;

class CarbonMixinTest extends TestCase
{
    public function testTryParse()
    {
        $datetime = Carbon::tryParse();
        $this->assertNull($datetime);

        $datetime = Carbon::tryParse($date = date('Y-m-d H:i:s'));
        $this->assertSame($date, $datetime->format('Y-m-d H:i:s'));

        $datetime = Carbon::tryParse($timestamp = time());
        $this->assertSame($timestamp, $datetime->getTimestamp());
    }

    public function testGetTimestampAsFloat()
    {
        $this->assertIsFloat(Carbon::now()->getTimestampAsFloat());
    }

    public function testGetTimestampAsString()
    {
        $now = Carbon::now();
        $this->assertIsNumeric($now->getTimestampAsString());
        $this->assertTrue(is_string($now->getTimestampAsString()));
    }

    public function testToRfc3339ExtendedString()
    {
        $now = Carbon::now();
        $this->assertTrue(is_string($now->toRfc3339ExtendedString()));
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

    public function testTryParseDate()
    {
        $datetime = Carbon::tryParseDate('2023-07-31');
        $this->assertSame($datetime->format('Y-m-d'), '2023-07-31');

        $datetime = Carbon::tryParseDate('2023/07/31');
        $this->assertSame($datetime->format('Y-m-d'), '2023-07-31');

        $datetime = Carbon::tryParseDate('2023.07.31');
        $this->assertSame($datetime->format('Y-m-d'), '2023-07-31');

        $datetime = Carbon::tryParseDate('2023年07月31日');
        $this->assertSame($datetime->format('Y-m-d'), '2023-07-31');

        $datetime = Carbon::tryParseDate('2023年07月31');
        $this->assertSame($datetime->format('Y-m-d'), '2023-07-31');

        $datetime = Carbon::tryParseDate(null);
        $this->assertNull($datetime);
    }

    public function testTryHelpers()
    {
        $this->assertSame('ok', Carbon::try(function () {
            return 'ok';
        }, 'fallback'));

        $this->assertSame('fallback', Carbon::try(function () {
            throw new \Exception('boom');
        }, 'fallback'));
    }

    public function testTryCreateFromFormatHelpers()
    {
        $date = Carbon::tryCreateFromFormat('Y-m-d', '2023-07-31');
        $this->assertSame('2023-07-31', $date->format('Y-m-d'));

        $this->assertNull(Carbon::tryCreateFromFormat('Y-m-d', 'invalid'));

        $date = Carbon::tryCreateFromFormats('2023/07/31', ['Y-m-d', 'Y/m/d']);
        $this->assertSame('2023-07-31', $date->format('Y-m-d'));

        $this->assertNull(Carbon::tryCreateFromFormats('invalid', ['Y-m-d']));
    }
}
