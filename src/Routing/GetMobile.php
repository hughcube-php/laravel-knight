<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/21
 * Time: 00:00
 */

namespace HughCube\Laravel\Knight\Routing;

use HughCube\Laravel\Knight\Exceptions\UserException;

/**
 * @mixin Action
 */
trait GetMobile
{
    /**
     * @return int|string|null
     */
    protected function getMobile(): ?int
    {
        return $this->getRequest()->get('mobile');
    }

    /**
     * @return int|string|null
     */
    protected function getIDDCode(): ?string
    {
        return $this->getRequest()->get('idd_code', 86);
    }

    /**
     * @param  int|string|null  $mobile
     * @param  int|string|null  $iddCode
     *
     * @return bool
     */
    protected function checkMobile($mobile, $iddCode = null): bool
    {
        if (!is_string($mobile) && !ctype_digit(strval($mobile))) {
            return false;
        }

        $pattern = '/^(13[0-9]|14[0-9]|15[0-9]|17[0-9]|18[0-9]|19[0-9])\d{8}$/';

        return false != preg_match($pattern, $mobile);
    }

    /**
     * @return mixed
     * @throws UserException
     *
     */
    protected function emptyMobileResponse()
    {
        throw new UserException('手机号码为空!');
    }

    /**
     * @return mixed
     * @throws UserException
     *
     */
    protected function invalidMobileResponse()
    {
        throw new UserException('手机号码错误!');
    }
}
