<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/2
 * Time: 00:17.
 */

namespace HughCube\Laravel\Knight\Traits;

use HughCube\Laravel\Knight\Ide\Http\KIdeRequest as IdeRequest;
use Illuminate\Http\Request;
use Laravel\Lumen\Http\Request as LumenRequest;

trait GetRequest
{
    /**
     * @return Request|IdeRequest|\Request|LumenRequest
     */
    public function getRequest(): Request
    {
        return request();
    }
}
