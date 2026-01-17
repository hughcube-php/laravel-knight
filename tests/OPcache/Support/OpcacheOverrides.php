<?php

namespace HughCube\Laravel\Knight\Tests\OPcache;

use PHPUnit\Framework\Assert;

class OpcacheTestOverrides
{
    public static bool $extensionLoaded = true;
    public static bool $opcacheStatusEnabled = false;
    public static bool $opcacheResetResult = false;

    public static function resetDefaults(): void
    {
        if (!\function_exists('opcache_get_status')) {
            Assert::markTestSkipped('opcache_get_status is not available.');
        }

        self::$extensionLoaded = \extension_loaded('Zend OPcache');
        self::$opcacheStatusEnabled = \opcache_get_status() !== false;
        self::$opcacheResetResult = false;
    }
}
