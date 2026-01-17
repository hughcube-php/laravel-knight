<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2026/1/6
 * Time: 16:13.
 */

namespace HughCube\Laravel\Knight\Http;

class JsonResponse extends \Illuminate\Http\JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = [], $options = 0, $json = false)
    {
        if ($options === 0) {
            $options = self::DEFAULT_ENCODING_OPTIONS;
        }

        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $options |= JSON_INVALID_UTF8_SUBSTITUTE;
        } elseif (defined('JSON_INVALID_UTF8_IGNORE')) {
            $options |= JSON_INVALID_UTF8_IGNORE;
        }

        parent::__construct($data, $status, $headers, $options, $json);
    }
}
