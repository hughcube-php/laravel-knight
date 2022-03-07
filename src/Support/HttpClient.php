<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 16:09.
 */

namespace HughCube\Laravel\Knight\Support;

use HughCube\GuzzleHttp\HttpClientTrait;

/**
 * @deprecated Use \HughCube\GuzzleHttp\HttpClientTrait
 */
trait HttpClient
{
    use HttpClientTrait;
}
