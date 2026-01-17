<?php

namespace HughCube\Laravel\Knight\Tests\Cache\Listeners;

use HughCube\Laravel\Knight\Cache\Listeners\LogMissedKey;
use HughCube\Laravel\Knight\Tests\TestCase;

class LogMissedKeyTest extends TestCase
{
    public function testHandleLogsCacheMiss()
    {
        $handler = $this->setupTestLogHandler();

        $listener = new LogMissedKey();
        $event = $this->newCacheEvent('array', 'missing-key', ['tag-a', 'tag-b']);
        $storeName = property_exists($event, 'storeName') ? $event->storeName : null;

        $listener->handle($event);

        if ($handler !== null) {
            $expectedMessage = sprintf(
                'cache missed: store: %s, key: %s, tags: %s',
                $storeName,
                $event->key,
                implode(',', $event->tags)
            );

            $found = false;
            foreach ($handler->getRecords() as $record) {
                $message = $record['message'] ?? ($record->message ?? '');
                if ($message === $expectedMessage) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Expected cache miss log message not found');
        } else {
            $this->assertTrue(true, 'Log handler not available, skipping log assertion');
        }
    }
}
