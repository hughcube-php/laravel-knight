<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/1/16
 * Time: 11:49.
 */

namespace HughCube\Laravel\Knight\Testing\Traits;

use Illuminate\Foundation\Testing\TestCase;

/**
 * @mixin TestCase
 */
trait TestJobCase
{
    abstract public function getJob(): object;

    public function testAction()
    {
        $job = $this->getJob();

        if (method_exists($job, 'setLogChannel')) {
            $job->setLogChannel('stdout');
        }

        $job->handle();

        $this->assertTrue(true);
    }
}
