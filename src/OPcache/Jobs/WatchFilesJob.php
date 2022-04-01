<?php

namespace HughCube\Laravel\Knight\OPcache\Jobs;

use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Throwable;

class WatchFilesJob extends Job
{
    use HttpClientTrait;

    public function rules(): array
    {
        return [
            'url'     => ['string', 'nullable'],
            'timeout' => ['integer', 'default:30'],
        ];
    }

    /**
     * @return void
     */
    protected function action(): void
    {
        $url = $this->getUrl();
        if (!PUrl::isUrlString($url)) {
            $this->warning('Remote interface URL cannot be found!');

            return;
        }

        try {
            $response = $this->getHttpClient()->get($this->getUrl(), [
                RequestOptions::TIMEOUT => floatval($this->p()->get('timeout')),
            ]);
            $results = json_decode($response->getBody()->getContents(), true);
        } catch (Throwable $exception) {
            $this->warning(sprintf('http error: %s!', $exception->getMessage()));

            return;
        }

        /** debug log */
        $count = Arr::get($results, 'data.count', 0);
        $message = 'watch OPcache files, count: %s, status: %s, url: %s';
        $this->info(sprintf($message, $count, $response->getStatusCode(), $url));
    }

    protected function getUrl(): string
    {
        $url = $this->p()->get('url', 'knight_opcache_scripts');
        if (PUrl::isUrlString($url)) {
            return $url;
        }

        if (Route::has($url)) {
            return route($url);
        }

        return URL::to($url);
    }
}
