<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/1
 * Time: 14:36.
 */

namespace HughCube\Laravel\Knight\Tests\Traits;

use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\Laravel\Knight\Traits\Validation;

class ValidationTest extends TestCase
{
    public function testMake()
    {
        $this->assertNoException(function () {
            $instance = new class() {
                use Validation;
            };

            $this->callMethod($instance, 'validate', [[]]);
        });
    }
}
