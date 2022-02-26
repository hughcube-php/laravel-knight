<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/4
 * Time: 7:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Routing;

use Illuminate\Validation\ValidationException;

class Action
{
    use \HughCube\Laravel\Knight\Routing\Action;

    /**
     * @return mixed
     * @throws ValidationException
     */
    protected function action()
    {
        return $this->getParameter()->all();
    }

    public function rules(): array
    {
        return [
            'uuid' => 'string',
        ];
    }
}
