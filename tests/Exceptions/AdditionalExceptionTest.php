<?php

namespace HughCube\Laravel\Knight\Tests\Exceptions;

use HughCube\Laravel\Knight\Exceptions\ExceptionWithData;
use HughCube\Laravel\Knight\Exceptions\AuthUserInstanceExpectException;
use HughCube\Laravel\Knight\Exceptions\AuthUserNotAvailableException;
use HughCube\Laravel\Knight\Exceptions\MobileEmptyException;
use HughCube\Laravel\Knight\Exceptions\MobileInvalidException;
use HughCube\Laravel\Knight\Exceptions\NotCloseTransactionException;
use HughCube\Laravel\Knight\Exceptions\NotExtendedHttpException;
use HughCube\Laravel\Knight\Exceptions\OptimisticLockException;
use HughCube\Laravel\Knight\Exceptions\UserException;
use HughCube\Laravel\Knight\Exceptions\ValidatePinCodeException;
use HughCube\Laravel\Knight\Exceptions\ValidateSignatureException;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Auth\AuthenticationException;

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

    public function testUserExceptionHierarchy()
    {
        $exception = new MobileEmptyException('empty');
        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertSame('empty', $exception->getMessage());

        $exception = new MobileInvalidException('invalid');
        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertSame('invalid', $exception->getMessage());

        $exception = new ValidatePinCodeException('pin');
        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertSame('pin', $exception->getMessage());

        $exception = new ValidateSignatureException('signature');
        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertSame('signature', $exception->getMessage());
    }

    public function testAuthExceptionsExtendAuthenticationException()
    {
        $exception = new AuthUserNotAvailableException('not-available');
        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertSame('not-available', $exception->getMessage());

        $exception = new AuthUserInstanceExpectException('expect-instance');
        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertSame('expect-instance', $exception->getMessage());
    }

    public function testOptimisticLockExceptionIsRuntimeException()
    {
        $exception = new OptimisticLockException('conflict');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('conflict', $exception->getMessage());
    }
}
