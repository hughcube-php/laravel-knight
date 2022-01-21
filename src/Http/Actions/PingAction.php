<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 21:36.
 */

namespace HughCube\Laravel\Knight\Http\Actions;

use HughCube\Laravel\Knight\Routing\Action;

class PingAction
{
    use Action;

    public function action(): string
    {
        return 'done';
    }
}
