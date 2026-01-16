<?php

namespace HughCube\Laravel\Knight\Tests\OPcache;

use Exception;
use HughCube\Laravel\Knight\OPcache\LoadedOPcacheExtension;
use HughCube\Laravel\Knight\Tests\TestCase;

class LoadedOPcacheExtensionTest extends TestCase
{
    public function testLoadedOPcacheExtension()
    {
        $checker = new class() {
            use LoadedOPcacheExtension;

            public function callCheck(): void
            {
                $this->loadedOPcacheExtension();
            }
        };

        if (!extension_loaded('Zend OPcache')) {
            $this->expectException(Exception::class);
            $checker->callCheck();
            return;
        }

        $checker->callCheck();
        $this->assertTrue(true);
    }
}
