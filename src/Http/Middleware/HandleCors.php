<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/29
 * Time: 16:32.
 */

namespace HughCube\Laravel\Knight\Http\Middleware;

use Illuminate\Http\Request;

class HandleCors extends \Illuminate\Http\Middleware\HandleCors
{
    protected function hasMatchingPath(Request $request): bool
    {
        return true;
    }
}
