<?php

namespace HughCube\Laravel\Knight\OPcache\Jobs;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\Laravel\Knight\Support\HttpClient;
use HughCube\PUrl\Url;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

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
     * @return mixed
     * @throws GuzzleException
     */
    protected function getResponse(): mixed
    {
        $url = Url::instance(route('knight_opcache_scripts'));
        $response = $this->getHttpClient()->get($url, [RequestOptions::TIMEOUT => 10.0]);

        $results = json_decode($response->getBody()->getContents(), true);

        /** debug log */
        $count = Arr::get($results, 'data.count');
        $message = 'watch OPcache files, count: %s, status: %s, url: %s';
        Log::debug(sprintf($message, $count, $response->getStatusCode(), $url->toString()));

        return $results;
    }
}
