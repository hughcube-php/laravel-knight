<?php

namespace HughCube\Laravel\Knight\Tests\OPcache\Views;

use HughCube\Laravel\Knight\Tests\TestCase;

class OpcacheViewTest extends TestCase
{
    public function testViewRendersWhenOpcacheEnabled()
    {
        if (!extension_loaded('Zend OPcache') || !function_exists('opcache_get_status')) {
            $this->markTestSkipped('Zend OPcache extension is not available.');
        }

        $status = opcache_get_status(false);
        if ($status === false) {
            $this->markTestSkipped('OPcache is not enabled for CLI.');
        }

        $path = base_path('src/OPcache/Views/opcache.php');
        $cwd = getcwd();

        ob_start();
        try {
            chdir(dirname($path));
            include $path;
        } finally {
            $output = ob_get_clean();
            if ($cwd !== false) {
                chdir($cwd);
            }
        }

        $this->assertStringContainsString('<html', $output);
        $this->assertStringContainsString('OpCache', $output);
    }
}
