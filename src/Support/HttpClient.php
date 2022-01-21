<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 16:09.
 */

namespace HughCube\Laravel\Knight\Support;

use GuzzleHttp\Client;

trait HttpClient
{
    private ?Client $httpClient = null;

    /**
     * @return Client
     */
    protected function getHttpClient(): Client
    {
        if (!$this->httpClient instanceof Client) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }
}
