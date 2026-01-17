<?php

namespace HughCube\Laravel\Knight\Tests\Routing;

use HughCube\Laravel\Knight\Routing\Controller;
use HughCube\Laravel\Knight\Routing\ListQuery;
use HughCube\Laravel\Knight\Routing\SimpleListQuery;
use HughCube\Laravel\Knight\Routing\SimplePaginateQuery;
use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class QueryItem extends EloquentModel
{
    protected $table = 'query_items';
    public $timestamps = false;
    protected $guarded = [];
}

abstract class BaseQueryAction extends Controller
{
    public Request $request;
    protected ?ParameterBag $parameterBagOverride = null;

    protected function getRequest(): Request
    {
        return $this->request;
    }

    protected function p($key = null, $default = null)
    {
        $this->parameterBagOverride ??= new ParameterBag($this->request->all());

        if (null === $key) {
            return $this->parameterBagOverride;
        }

        return $this->parameterBagOverride->get($key, $default);
    }
}

class ListQueryAction extends BaseQueryAction
{
    use ListQuery;

    protected function makeQuery(): ?EloquentBuilder
    {
        return QueryItem::query();
    }
}

class SimpleListQueryAction extends BaseQueryAction
{
    use SimpleListQuery;

    protected function makeQuery(): ?EloquentBuilder
    {
        return QueryItem::query();
    }
}

class SimplePaginateQueryAction extends BaseQueryAction
{
    use SimplePaginateQuery;

    protected function makeQuery(): ?EloquentBuilder
    {
        return QueryItem::query();
    }
}

class QueryTraitsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('query_items');
        Schema::create('query_items', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name')->nullable();
        });

        QueryItem::query()->insert([
            ['name' => 'a'],
            ['name' => 'b'],
            ['name' => 'c'],
            ['name' => 'd'],
            ['name' => 'e'],
        ]);
    }

    public function testListQueryReturnsSuccessPayload()
    {
        $action = new ListQueryAction();
        $action->request = Request::create('/', 'GET', ['page_size' => 2]);

        $response = $this->callMethod($action, 'action');
        $payload = json_decode($response->getContent(), true);

        $this->assertSame('Success', $payload['Code']);
        $this->assertSame(2, $payload['Data']['page_size']);
        $this->assertCount(2, $payload['Data']['list']);
        $this->assertArrayNotHasKey('count', $payload['Data']);
    }

    public function testSimpleListQueryUsesLegacyResponse()
    {
        $action = new SimpleListQueryAction();
        $action->request = Request::create('/', 'GET', ['page_size' => 3]);

        $response = $this->callMethod($action, 'action');
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $payload['code']);
        $this->assertSame('success', $payload['message']);
        $this->assertCount(3, $payload['data']['list']);
    }

    public function testSimplePaginateQueryReturnsCountAndPage()
    {
        $action = new SimplePaginateQueryAction();
        $action->request = Request::create('/', 'GET', ['page' => 2, 'page_size' => 2]);

        $response = $this->callMethod($action, 'action');
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['data']['page']);
        $this->assertSame(2, $payload['data']['page_size']);
        $this->assertSame(5, $payload['data']['count']);
        $this->assertCount(2, $payload['data']['list']);
    }

    public function testListQueryDefaultHelpersReturnNull()
    {
        $action = new ListQueryAction();
        $action->request = Request::create('/', 'GET');

        $this->assertSame([], $this->callMethod($action, 'rules'));
        $this->assertNull($this->callMethod($action, 'getPage'));
        $this->assertNull($this->callMethod($action, 'queryCount', [QueryItem::query()]));
    }
}
