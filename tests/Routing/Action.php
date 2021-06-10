<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/4
 * Time: 7:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Routing;

class Action
{
    use \HughCube\Laravel\Knight\Routing\Action;

    protected function rules()
    {
        return [
            'uuid' => ['string'],
        ];
    }

    protected function action()
    {
        return $this->parameter()->all();
    }
}
