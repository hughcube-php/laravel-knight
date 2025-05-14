<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2025/2/6
 * Time: 11:00.
 */

namespace HughCube\Laravel\Knight\Contracts\Support;

interface AccessSecret
{
    public function getAccessSecret(): ?string;

    public function getAccessToken(): ?string;
}
