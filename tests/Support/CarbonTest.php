<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 19:11.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonInterface;
use HughCube\Laravel\Knight\Support\Carbon;
use HughCube\Laravel\Knight\Tests\TestCase;

class CarbonTest extends TestCase
{
    public function testFromDate()
    {
        $dateFormats = [
            CarbonInterface::DEFAULT_TO_STRING_FORMAT, CarbonInterface::RFC7231_FORMAT,
            CarbonInterface::MOCK_DATETIME_FORMAT,
        ];
        foreach ($dateFormats as $dateFormat) {
            $date = date($dateFormat, ($timestamp = time()));
            $dateTime = Carbon::fromDate($date, $dateFormat);

            $this->assertInstanceOf(BaseCarbon::class, $dateTime);
            $this->assertInstanceOf(Carbon::class, $dateTime);
            $this->assertSame($dateTime->getTimestamp(), $timestamp);
        }
    }

    public function testAsDate()
    {
        $timestamp = time();
        $dateFormats = [
            CarbonInterface::DEFAULT_TO_STRING_FORMAT, CarbonInterface::RFC7231_FORMAT,
            CarbonInterface::MOCK_DATETIME_FORMAT,
        ];

        foreach ($dateFormats as $dateFormat) {
            $date = date($dateFormat, $timestamp);
            $dateTime = Carbon::fromDate($date, $dateFormat);

            $this->assertSame($date, Carbon::asDate($dateTime, $dateFormat));
            $this->assertSame($date, Carbon::asDate($timestamp, $dateFormat));
        }
    }

    public function testIsPastDate()
    {
        $timestamp = time();
        $dateFormats = [
            CarbonInterface::DEFAULT_TO_STRING_FORMAT, CarbonInterface::RFC7231_FORMAT,
            CarbonInterface::MOCK_DATETIME_FORMAT,
        ];

        foreach ($dateFormats as $dateFormat) {
            $date = date($dateFormat, $timestamp - 1000);
            $this->assertTrue(Carbon::isPastDate($date, $dateFormat));

            $date = date($dateFormat, $timestamp + 1000);
            $this->assertFalse(Carbon::isPastDate($date, $dateFormat));
        }
    }

    public function testIsPastTimestamp()
    {
        $timestamp = time();
        $this->assertTrue(Carbon::isPastTimestamp($timestamp - 1000));

        $this->assertFalse(Carbon::isPastTimestamp(null));
        $this->assertFalse(Carbon::isPastTimestamp('now'));
        $this->assertFalse(Carbon::isPastTimestamp($timestamp + 1000));
    }

    public function testGetTimestampAsFloat()
    {
        $this->assertIsFloat(Carbon::now()->getTimestampAsFloat());
        $this->assertIsNumeric(strval(Carbon::now()->getTimestampAsFloat()));
    }
}
