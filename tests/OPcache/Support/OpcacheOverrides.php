<?php

namespace HughCube\Laravel\Knight\Tests\OPcache;

class OpcacheTestOverrides
{
    public static bool $extensionLoaded = true;
    public static bool $opcacheStatusEnabled = false;
    public static bool $opcacheResetResult = false;

    public static function resetDefaults(): void
    {
        if (!\function_exists('opcache_get_status')) {
            throw new \PHPUnit\Framework\SkippedWithMessageException(
                'opcache_get_status is not available.'
            );
        }

        self::$extensionLoaded = \extension_loaded('Zend OPcache');
        self::$opcacheStatusEnabled = \opcache_get_status() !== false;
        self::$opcacheResetResult = false;
    }
}
