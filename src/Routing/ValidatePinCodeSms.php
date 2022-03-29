<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/17
 * Time: 17:33.
 */

namespace HughCube\Laravel\Knight\Routing;

/**
 * @mixin Action
 */
trait ValidatePinCodeSms
{
    use GetMobile;

    protected function getPinCode()
    {
        return $this->getRequest()->get('pincode');
    }

    protected function validatePinCode(): bool
    {
        return $this->runValidatePinCode($this->getMobile(), $this->getIDDCode(), $this->getPinCode());
    }

    abstract protected function runValidatePinCode($mobile, $iddCode, $pincode): bool;
}
