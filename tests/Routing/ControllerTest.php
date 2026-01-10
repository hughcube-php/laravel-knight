<?php

namespace HughCube\Laravel\Knight\Tests\Routing;

use HughCube\Laravel\Knight\Exceptions\NotExtendedHttpException;
use HughCube\Laravel\Knight\Routing\Controller;
use HughCube\Laravel\Knight\Tests\TestCase;

class ControllerTest extends TestCase
{
    public function testActionThrowsException()
    {
        $controller = new Controller();

        $this->expectException(NotExtendedHttpException::class);
        $this->expectExceptionMessage('Further extensions to the request are required for the server to fulfill it.');

        // 使用反射来调用 protected 方法 action，或者直接调用 __invoke 如果它暴露了
        // Controller use Action trait, Action trait has __invoke which calls invoke() -> action()
        
        // Mock 必要的依赖，因为 Action trait 会尝试从容器获取 Request 等
        // 由于我们在 TestCase 中，Application 已经启动，Request 应该也有默认的
        
        $controller();
    }
}
