<?php

namespace HughCube\Laravel\Knight\Tests\Exceptions;

use HughCube\Laravel\Knight\Exceptions\ExceptionWithData;
use HughCube\Laravel\Knight\Exceptions\NotCloseTransactionException;
use HughCube\Laravel\Knight\Exceptions\NotExtendedHttpException;
use HughCube\Laravel\Knight\Tests\TestCase;

class AdditionalExceptionTest extends TestCase
{
    public function testExceptionWithDataReturnsPayload()
    {
        $payload = ['foo' => 'bar'];
        $exception = new ExceptionWithData($payload, 'message', 123);

        $this->assertSame($payload, $exception->getData());
        $this->assertSame('message', $exception->getMessage());
        $this->assertSame(123, $exception->getCode());

        $emptyException = new ExceptionWithData(null);
        $this->assertSame([], $emptyException->getData());
    }

    public function testNotExtendedHttpExceptionUses510Status()
    {
        $exception = new NotExtendedHttpException('blocked');

        $this->assertSame(510, $exception->getStatusCode());
        $this->assertSame('blocked', $exception->getMessage());
    }

    public function testNotCloseTransactionExceptionStoresConnection()
    {
        $connection = $this->app['db']->connection();
        $exception = new NotCloseTransactionException($connection, 'open');

        $this->assertSame($connection, $exception->getConnection());
        $this->assertSame('open', $exception->getMessage());
    }
}
