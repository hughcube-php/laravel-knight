<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 21:36.
 */

namespace HughCube\Laravel\Knight\Tests\Http\Actions;

use HughCube\Laravel\Knight\Http\Actions\RequestShowAction;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Psr\SimpleCache\InvalidArgumentException;

class RequestShowActionTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    public function testRun()
    {
        /** @var RequestShowAction $action */
        $action = $this->app->make(RequestShowAction::class);

        $this->assertInstanceOf(JsonResponse::class, $action());
    }
}
