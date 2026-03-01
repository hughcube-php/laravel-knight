<?php

namespace HughCube\Laravel\Knight\Tests\Exceptions;

use HughCube\Laravel\Knight\Exceptions\BusinessRuleException;
use HughCube\Laravel\Knight\Tests\TestCase;

class BusinessRuleExceptionTest extends TestCase
{
    public function testIsRuntimeException()
    {
        $exception = new BusinessRuleException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testNotUserException()
    {
        $exception = new BusinessRuleException();

        $this->assertNotInstanceOf(
            \HughCube\Laravel\Knight\Exceptions\UserException::class,
            $exception
        );
    }

    public function testDefaultMessage()
    {
        $exception = new BusinessRuleException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCustomMessage()
    {
        $exception = new BusinessRuleException('余额不足');

        $this->assertSame('余额不足', $exception->getMessage());
    }

    public function testCustomCode()
    {
        $exception = new BusinessRuleException('错误', 10001);

        $this->assertSame('错误', $exception->getMessage());
        $this->assertSame(10001, $exception->getCode());
    }

    public function testPreviousException()
    {
        $previous = new \InvalidArgumentException('原始错误');
        $exception = new BusinessRuleException('业务错误', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception->getPrevious());
    }

    public function testCanBeCaught()
    {
        $caught = false;

        try {
            throw new BusinessRuleException('测试异常');
        } catch (BusinessRuleException $e) {
            $caught = true;
            $this->assertSame('测试异常', $e->getMessage());
        }

        $this->assertTrue($caught);
    }

    public function testCanBeCaughtAsRuntimeException()
    {
        $caught = false;

        try {
            throw new BusinessRuleException('运行时异常');
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertInstanceOf(BusinessRuleException::class, $e);
        }

        $this->assertTrue($caught);
    }
}
