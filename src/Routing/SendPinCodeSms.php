<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/17
 * Time: 17:33.
 */

namespace HughCube\Laravel\Knight\Routing;

use HughCube\Laravel\Knight\Exceptions\UserException;

/**
 * @mixin Action
 */
trait SendPinCodeSms
{
    use GetMobile;

    /**
     * @throws UserException
     *
     * @return mixed|void
     */
    protected function action()
    {
        $mobile = $this->getMobile();
        $iddCode = $this->getIDDCode();

        if (empty($mobile) || empty($iddCode)) {
            return $this->emptyMobileResponse();
        }

        if (!$this->checkMobile($mobile, $iddCode)) {
            return $this->invalidMobileResponse();
        }

        $pinCode = $this->getPinCode($mobile, $iddCode);

        if ($this->enableSend()) {
            $this->send($mobile, $iddCode, $pinCode);
        }

        return $this->asResponse();
    }

    protected function enableSend(): bool
    {
        return true;
    }

    /**
     * @param int|string $mobile
     * @param int|string $iddCode
     *
     * @return string
     */
    abstract protected function getPinCode($mobile, $iddCode): string;

    /**
     * @param int|string $mobile
     * @param int|string $iddCode
     * @param mixed      $pinCode
     *
     * @return void
     */
    abstract protected function send($mobile, $iddCode, $pinCode): void;
}
