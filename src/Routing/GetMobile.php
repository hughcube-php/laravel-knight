<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/21
 * Time: 00:00.
 */

namespace HughCube\Laravel\Knight\Routing;

use HughCube\Laravel\Knight\Exceptions\MobileEmptyException;
use HughCube\Laravel\Knight\Exceptions\MobileInvalidException;

/**
 * @mixin Action
 */
trait GetMobile
{
    /**
     * @return int|string|null
     */
    protected function getMobile(): ?string
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
     * @param int|string|null $mobile
     * @param int|string|null $iddCode
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
     * @throws MobileEmptyException
     *
     * @return mixed
     */
    protected function emptyMobileResponse()
    {
        throw new MobileEmptyException('手机号码为空!');
    }

    /**
     * @throws MobileInvalidException
     *
     * @return mixed
     */
    protected function invalidMobileResponse()
    {
        throw new MobileInvalidException('手机号码错误!');
    }
}
