<?php

namespace HughCube\Laravel\Knight\OPcache\Jobs;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\Laravel\Knight\Support\HttpClient;
use HughCube\PUrl\Url;
use Psr\Http\Message\ResponseInterface;

class WatchFilesJob extends \HughCube\Laravel\Knight\Queue\Job
{
    use HttpClient;

    /**
     * @return void
     * @throws GuzzleException
     */
    protected function action(): void
    {
        $this->getResponse();
    }

    /**
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function getResponse(): ResponseInterface
    {
        $url = Url::instance(route('knight_opcache_scripts'));
        return $this->getHttpClient()->get($url, [RequestOptions::TIMEOUT => 10.0]);
    }
}
