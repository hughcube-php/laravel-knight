<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 19:11.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use DateTimeImmutable;
use DateTimeZone;
use HughCube\Laravel\Knight\Support\Carbon as KnightCarbon;
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

    public function testKnightCarbonFromDate()
    {
        $this->assertNull(KnightCarbon::fromDate(null));

        $timestamp = 1700000000;
        $fromTimestamp = KnightCarbon::fromDate($timestamp);
        $this->assertInstanceOf(KnightCarbon::class, $fromTimestamp);
        $this->assertSame($timestamp, $fromTimestamp->getTimestamp());

        $dateTime = new DateTimeImmutable('2023-01-01 00:00:00', new DateTimeZone('UTC'));
        $fromDateTime = KnightCarbon::fromDate($dateTime);
        $this->assertInstanceOf(KnightCarbon::class, $fromDateTime);
        $this->assertSame($dateTime->getTimestamp(), $fromDateTime->getTimestamp());

        $fromFormat = KnightCarbon::fromDate('2023-08-01', 'Y-m-d');
        $this->assertInstanceOf(KnightCarbon::class, $fromFormat);
        $this->assertSame('2023-08-01', $fromFormat->format('Y-m-d'));
    }

    public function testKnightCarbonFormattingAndChecks()
    {
        $dateTime = new DateTimeImmutable('2023-01-01 00:00:00', new DateTimeZone('UTC'));
        $this->assertSame('2023-01-01', KnightCarbon::asDate($dateTime, 'Y-m-d'));

        $this->assertTrue(KnightCarbon::isPastDate('2000-01-01 00:00:00'));
        $this->assertTrue(KnightCarbon::isPastTimestamp(time() - 5));
        $this->assertFalse(KnightCarbon::isPastTimestamp(time() + 5));
    }

    public function testKnightCarbonRfc3339Helpers()
    {
        $date = KnightCarbon::createFromRfc3339('2023-01-01T00:00:00+00:00');
        $this->assertInstanceOf(KnightCarbon::class, $date);

        $extended = KnightCarbon::createFromRfc3339Extended('2023-01-01T00:00:00.123+00:00');
        $this->assertInstanceOf(KnightCarbon::class, $extended);

        $extendedString = $extended->toRfc3339ExtendedString();
        $this->assertStringContainsString('.', $extendedString);
        $this->assertStringEndsWith('+00:00', $extendedString);
    }
}
