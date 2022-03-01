<?php

namespace HughCube\Laravel\Knight\OPcache\Jobs;

use GuzzleHttp\RequestOptions;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Support\HttpClient;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Throwable;

class WatchFilesJob extends Job
{
    use HttpClient;

    public function rules(): array
    {
        return [
            'url' => ['string', 'nullable'],
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
            $message = sprintf('Description Failed to run the %s job, ', $this->getName());
            Log::warning(sprintf('%s, %s', $message, 'Remote interface URL cannot be found!'));

            return;
        }

        try {
            $response = $this->getHttpClient()->get($this->getUrl(), [
                RequestOptions::TIMEOUT => floatval($this->p()->get('timeout')),
            ]);
            $results = json_decode($response->getBody()->getContents(), true);
        } catch (Throwable $exception) {
            $message = sprintf('Description Failed to run the %s job ', $this->getName());
            Log::warning(sprintf('%s, http error: %s', $message, $exception->getMessage()));

            return;
        }

        /** debug log */
        $count = Arr::get($results, 'data.count', 0);
        $message = 'watch OPcache files, count: %s, status: %s, url: %s';
        Log::debug(sprintf($message, $count, $response->getStatusCode(), $url));
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
