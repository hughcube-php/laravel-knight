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
        $hasMatchingPath = $this->container['config']->get('cors.has_matching_path');
        if (null !== $hasMatchingPath) {
            return $hasMatchingPath;
        }

        return parent::hasMatchingPath($request);
    }
}
