<?php

namespace HughCube\Laravel\Knight\Tests\Events;

use HughCube\Laravel\Knight\Contracts\Database\HasWalHandler;
use HughCube\Laravel\Knight\Events\WalChangesDetected;
use HughCube\Laravel\Knight\Tests\TestCase;

class WalChangesDetectedTest extends TestCase
{
    public function testConstructorSetsProperties()
    {
        $handler = $this->createWalHandler('test_table');
        $ids = [1, 2, 3];

        $event = new WalChangesDetected($handler, $ids);

        $this->assertSame($handler, $event->handler);
        $this->assertSame($ids, $event->ids);
    }

    public function testWithIntegerIds()
    {
        $handler = $this->createWalHandler('users');
        $ids = [10, 20, 30];

        $event = new WalChangesDetected($handler, $ids);

        $this->assertCount(3, $event->ids);
        $this->assertSame(10, $event->ids[0]);
        $this->assertSame(20, $event->ids[1]);
        $this->assertSame(30, $event->ids[2]);
    }

    public function testWithStringIds()
    {
        $handler = $this->createWalHandler('users');
        $ids = ['abc-123', 'def-456', 'ghi-789'];

        $event = new WalChangesDetected($handler, $ids);

        $this->assertCount(3, $event->ids);
        $this->assertSame('abc-123', $event->ids[0]);
    }

    public function testWithMixedTypeIds()
    {
        $handler = $this->createWalHandler('items');
        $ids = [1, 'uuid-abc', 42, 'uuid-def'];

        $event = new WalChangesDetected($handler, $ids);

        $this->assertCount(4, $event->ids);
        $this->assertSame(1, $event->ids[0]);
        $this->assertSame('uuid-abc', $event->ids[1]);
    }

    public function testWithEmptyIds()
    {
        $handler = $this->createWalHandler('orders');
        $ids = [];

        $event = new WalChangesDetected($handler, $ids);

        $this->assertSame($handler, $event->handler);
        $this->assertSame([], $event->ids);
    }

    public function testWithSingleId()
    {
        $handler = $this->createWalHandler('logs');
        $ids = [999];

        $event = new WalChangesDetected($handler, $ids);

        $this->assertCount(1, $event->ids);
        $this->assertSame(999, $event->ids[0]);
    }

    public function testHandlerTableName()
    {
        $handler = $this->createWalHandler('my_custom_table');
        $event = new WalChangesDetected($handler, [1]);

        $this->assertSame('my_custom_table', $event->handler->getTable());
    }

    /**
     * @return HasWalHandler
     */
    private function createWalHandler(string $table)
    {
        return new class($table) implements HasWalHandler {
            /** @var string */
            private $table;

            public function __construct(string $table)
            {
                $this->table = $table;
            }

            public function getTable()
            {
                return $this->table;
            }

            public function onKnightModelChanged(): void
            {
            }
        };
    }
}
