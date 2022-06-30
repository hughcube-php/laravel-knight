<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:51
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\Version;
use HughCube\Laravel\Knight\Tests\TestCase;

class VersionTest extends TestCase
{
    public function testPad()
    {
        $version = '1';
        $this->assertSame(2, substr_count(Version::pad($version), '.'));
        $this->assertSame(4, substr_count(Version::pad($version, 5), '.'));

        $version = '1.1.1.1.1.1.1.1.1.1.1.1';
        $this->assertSame(2, substr_count(Version::pad($version), '.'));
        $this->assertSame(4, substr_count(Version::pad($version, 5), '.'));
    }
}
