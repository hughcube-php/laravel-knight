<?php

namespace HughCube\Laravel\Knight\Tests\OPcache\Commands;

use HughCube\Laravel\Knight\OPcache\Commands\CreatePreloadCommand;
use HughCube\Laravel\Knight\Tests\TestCase;

class CreatePreloadCommandTest extends TestCase
{
    public function testServerCommandUsesCreatePreloadScript()
    {
        $command = new CreatePreloadCommand();

        $serverCommand = $this->callMethod($command, 'serverCommand');

        $this->assertIsArray($serverCommand);
        $this->assertCount(2, $serverCommand);
        $this->assertNotEmpty($serverCommand[0]);
        $this->assertTrue(str_ends_with($serverCommand[1], 'create_preload.php'));
    }
}
