<?php

namespace HughCube\Laravel\Knight\Tests\OPcache;

class OpcacheTestOverrides
{
    public static bool $extensionLoaded = true;
    public static bool $opcacheStatusEnabled = false;
    public static bool $opcacheResetResult = false;

    public static function resetDefaults(): void
    {
        self::$extensionLoaded = \extension_loaded('Zend OPcache');
        self::$opcacheStatusEnabled = \function_exists('opcache_get_status')
            && \opcache_get_status() !== false;
        self::$opcacheResetResult = false;
    }
}
