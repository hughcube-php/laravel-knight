<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 21:36.
 */

namespace HughCube\Laravel\Knight\Http\Actions;

use HughCube\Laravel\Knight\Routing\Controller;

class PingAction extends Controller
{
    protected function action(): string
    {
        return 'done';
    }
}
