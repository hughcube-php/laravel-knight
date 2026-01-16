<?php

namespace HughCube\Laravel\Knight\Tests\Cache\Listeners;

use HughCube\Laravel\Knight\Cache\Listeners\LogMissedKey;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Support\Facades\Log;

class LogMissedKeyTest extends TestCase
{
    public function testHandleLogsCacheMiss()
    {
        Log::spy();

        $listener = new LogMissedKey();
        $event = new CacheMissed('array', 'missing-key', ['tag-a', 'tag-b']);

        $listener->handle($event);

        Log::shouldHaveReceived('debug')
            ->once()
            ->with('cache missed: store: array, key: missing-key, tags: tag-a,tag-b');
    }
}
