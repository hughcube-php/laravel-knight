<?php

namespace HughCube\Laravel\Knight\Tests\Traits;

use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\Laravel\Knight\Traits\Container as ContainerTrait;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Illuminate\Events\Dispatcher as BaseEventsDispatcher;
use Illuminate\Queue\QueueManager;
use Throwable;

class ContainerTraitTest extends TestCase
{
    public function testContainerConfigHelpers()
    {
        $instance = new TestContainerTraitUser();

        $this->assertSame($this->app, self::callMethod($instance, 'getContainer'));

        config(['app.env' => 'local', 'app.debug' => true]);

        $this->assertTrue(self::callMethod($instance, 'isContainerDebug'));
        $this->assertTrue(self::callMethod($instance, 'isContainerEnv', ['local']));
        $this->assertTrue(self::callMethod($instance, 'isContainerLocalEnv'));
        $this->assertTrue(self::callMethod($instance, 'isContainerTestEnv'));
        $this->assertFalse(self::callMethod($instance, 'isContainerProdEnv'));

        config(['app.env' => 'production', 'app.debug' => false]);

        $this->assertFalse(self::callMethod($instance, 'isContainerDebug'));
        $this->assertTrue(self::callMethod($instance, 'isContainerProdEnv'));

        $this->assertSame('production', self::callMethod($instance, 'getContainerConfig', ['app.env']));
        $this->assertSame('fallback', self::callMethod($instance, 'getContainerConfig', ['missing', 'fallback']));
    }

    public function testContainerServiceResolution()
    {
        $instance = new TestContainerTraitUser();

        $busDispatcher = new TestBusDispatcher();
        $eventsDispatcher = new BaseEventsDispatcher($this->app);
        $queueManager = new QueueManager($this->app);
        $exceptionHandler = new TestExceptionHandler();

        $this->app->instance(Dispatcher::class, $busDispatcher);
        $this->app->instance(EventsDispatcher::class, $eventsDispatcher);
        $this->app->instance(QueueManager::class, $queueManager);
        $this->app->instance(ExceptionHandler::class, $exceptionHandler);

        $this->assertSame($busDispatcher, self::callMethod($instance, 'getDispatcher'));
        $this->assertSame($eventsDispatcher, self::callMethod($instance, 'getEventsDispatcher'));
        $this->assertSame($queueManager, self::callMethod($instance, 'getQueueManager'));
        $this->assertSame($exceptionHandler, self::callMethod($instance, 'getExceptionHandler'));
    }
}

class TestContainerTraitUser
{
    use ContainerTrait;
}

class TestBusDispatcher implements Dispatcher
{
    public function dispatch($command)
    {
    }

    public function dispatchSync($command, $handler = null)
    {
    }

    public function dispatchNow($command, $handler = null)
    {
    }

    public function hasCommandHandler($command)
    {
        return false;
    }

    public function getCommandHandler($command)
    {
        return null;
    }

    public function pipeThrough(array $pipes)
    {
        return $this;
    }

    public function map(array $map)
    {
        return $this;
    }
}

class TestExceptionHandler implements ExceptionHandler
{
    public function report(Throwable $e)
    {
    }

    public function shouldReport(Throwable $e)
    {
        return false;
    }

    public function render($request, Throwable $e)
    {
        return null;
    }

    public function renderForConsole($output, Throwable $e)
    {
    }
}
