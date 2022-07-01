<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/2
 * Time: 00:17
 */

namespace HughCube\Laravel\Knight\Traits;

use HughCube\Laravel\Knight\Ide\Http\Request as IdeRequest;
use Illuminate\Http\Request;

trait GetRequest
{
    /**
     * @return null|Request|IdeRequest|\Request
     */
    public function getRequest(): Request
    {
        return request();
    }
}
