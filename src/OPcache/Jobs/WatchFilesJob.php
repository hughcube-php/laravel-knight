<?php

namespace HughCube\Laravel\Knight\OPcache\Jobs;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\Laravel\Knight\Support\HttpClient;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use JetBrains\PhpStorm\ArrayShape;

class WatchFilesJob extends \HughCube\Laravel\Knight\Queue\Job
{
    use HttpClient;

    #[ArrayShape([])]
    public function rules(): array
    {
        return [
            'url' => ['string', 'nullable'],
            'timeout' => ['integer', 'default:30'],
        ];
    }

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
        $url = $this->getUrl();
        $response = $this->getHttpClient()->get($this->getUrl(), [
            RequestOptions::TIMEOUT => $this->getUrl('timeout')
        ]);

        $results = json_decode($response->getBody()->getContents(), true);

        /** debug log */
        $count = Arr::get($results, 'data.count');
        $message = 'watch OPcache files, count: %s, status: %s, url: %s';
        Log::debug(sprintf($message, $count, $response->getStatusCode(), $url));

        return $results;
    }

    protected function getUrl()
    {
        $url = $this->get('url', 'knight_opcache_scripts');
        if (PUrl::isUrlString($url)) {
            return $url;
        }

        if (Route::has($url)) {
            return route($url);
        }

        return URL::to($url);
    }
}
