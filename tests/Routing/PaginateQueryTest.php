<?php

namespace HughCube\Laravel\Knight\Tests\Routing;

use HughCube\Laravel\Knight\Routing\Action;
use HughCube\Laravel\Knight\Routing\PaginateQuery;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mockery;

class PaginateQueryActionStub
{
    use Action, PaginateQuery {
        PaginateQuery::rules insteadof Action;
    }

    protected $builderMock;

    public function __construct($builderMock = null)
    {
        $this->builderMock = $builderMock;
    }

    protected function makeQuery(): ?Builder
    {
        return $this->builderMock;
    }
}

class PaginateQueryTest extends TestCase
{
    public function testPaginateQuery()
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('count')->andReturn(100);
        $builder->shouldReceive('limit')->with(10)->andReturnSelf();
        $builder->shouldReceive('offset')->with(0)->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(new Collection(range(1, 10)));

        $action = new PaginateQueryActionStub($builder);

        // 模拟请求参数
        $request = \Illuminate\Http\Request::create('/test', 'GET', ['page' => 1, 'page_size' => 10]);
        $this->app->instance('request', $request);

        $response = $action();

        $content = $response->getData(true);
        $this->assertSame('Success', $content['Code']);
        $this->assertSame(1, $content['Data']['page']);
        $this->assertSame(10, $content['Data']['page_size']);
        $this->assertSame(100, $content['Data']['count']);
        $this->assertCount(10, $content['Data']['list']);
    }
}
