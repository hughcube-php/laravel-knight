<?php

namespace HughCube\Laravel\Knight\Tests\Cache\Listeners;

use HughCube\Laravel\Knight\Cache\Listeners\LogMissedKey;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Facades\Log;

class LogMissedKeyTest extends TestCase
{
    public function testHandleLogsCacheMiss()
    {
        Log::spy();

        $listener = new LogMissedKey();
        $event = $this->newCacheEvent('array', 'missing-key', ['tag-a', 'tag-b']);
        $storeName = property_exists($event, 'storeName') ? $event->storeName : null;

        $listener->handle($event);

        Log::shouldHaveReceived('debug')
            ->once()
            ->with(sprintf(
                'cache missed: store: %s, key: %s, tags: %s',
                $storeName,
                $event->key,
                implode(',', $event->tags)
            ));
    }
}
