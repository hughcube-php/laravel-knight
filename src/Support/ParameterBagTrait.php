<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/17
 * Time: 22:21.
 */

namespace HughCube\Laravel\Knight\Support;

trait ParameterBagTrait
{
    /**
     * @var ParameterBag|null
     */
    protected $parameterBag = null;

    /**
     * @return mixed
     */
    abstract protected function loadParameters();

    /**
     * @param string|int $key
     * @param mixed      $default
     *
     * @return ParameterBag|mixed
     */
    protected function p($key = null, $default = null)
    {
        $this->loadParameters();

        if (null === $key) {
            return $this->parameterBag;
        }

        return $this->parameterBag->get($key, $default);
    }
}
