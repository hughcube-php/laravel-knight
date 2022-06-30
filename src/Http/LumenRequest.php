<?php

namespace HughCube\Laravel\Knight\Http;

use HughCube\Laravel\Knight\Mixin\Http\RequestMixin;
use Laravel\Lumen\Http\Request as HttpRequest;

/**
 * @deprecated 改为使用mixin实现
 * @see RequestMixin::class
 */
class LumenRequest extends HttpRequest
{
}
