<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/4
 * Time: 7:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Routing;

use JetBrains\PhpStorm\ArrayShape;

class Action
{
    use \HughCube\Laravel\Knight\Routing\Action;

    /**
     * @return array
     */
    public function action()
    {
        return $this->getParameter()->all();
    }

    #[ArrayShape(["uuid" => "string"])]
    public function rules(): array
    {
        return [
            "uuid" => "string"
        ];
    }
}
