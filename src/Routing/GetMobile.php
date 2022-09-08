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
use Illuminate\Support\Str;

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

        if(86 == $iddCode || null == $iddCode){
            return false != preg_match(Str::getMobilePattern(), $mobile);
        }

        return true;
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
        throw new MobileInvalidException('手机号码不正确!');
    }
}
