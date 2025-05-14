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

    /**
     * @return array
     */
    protected function action(): array
    {
        return $this->p()->all();
    }

    public function rules(): array
    {
        return [
            'uuid' => 'string',
        ];
    }
}
