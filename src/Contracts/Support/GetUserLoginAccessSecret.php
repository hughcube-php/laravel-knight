<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/27
 * Time: 21:47
 */

namespace HughCube\Laravel\Knight\Contracts\Support;

interface GetUserLoginAccessSecret
{
    public function getUserLoginAccessSecret(): ?string;
}
