<?php

namespace HughCube\Laravel\Knight\OPcache\Jobs;

use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Support\Str;
use HughCube\Laravel\Knight\Traits\Container;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Throwable;

class WatchFilesJob extends Job
{
    use Container;
    use HttpClientTrait;

    public function rules(): array
    {
        return [
            'url' => ['string', 'nullable'],
            'use_app_url' => ['boolean', 'default:1'],
            'timeout' => ['integer', 'default:30'],
        ];
    }

    /**
     * @return void
     */
    protected function action(): void
    {
        $url = $this->getUrl();
        if (!$url instanceof PUrl) {
            $this->warning('Remote interface URL cannot be found!');
            return;
        }

        /** 替换域名为 app.url */
        if ($this->p('use_app_url') && !Str::isIp($url->getHost())) {
            $appUrl = PUrl::parse($this->getContainerConfig()->get('app.url'));
            if ($appUrl instanceof PUrl) {
                $url = $url
                    ->withHost($appUrl->getHost())
                    ->withPort($appUrl->getPort())
                    ->withScheme($appUrl->getScheme());
            }
        }

        try {
            $response = $this->getHttpClient()->get($url, [
                RequestOptions::TIMEOUT => floatval($this->p()->get('timeout')),
                RequestOptions::ALLOW_REDIRECTS => ['max' => 5, 'referer' => true, 'track_redirects' => true],
            ]);
            $results = json_decode($response->getBody()->getContents(), true);
        } catch (Throwable $exception) {
            $this->warning(sprintf('http error: %s!', $exception->getMessage()));
            return;
        }

        $this->info(sprintf(
            'watch OPcache files, count: %s, status: %s, url: %s',
                $results['data']['count'] ?? null ?: 0,
            $response->getStatusCode(),
            $url->toString()
        ));
    }

    protected function getUrl(): ?PUrl
    {
        $url = $this->p()->get('url', 'knight.opcache.scripts');
        if (PUrl::isUrlString($url)) {
            return $url;
        }

        return PUrl::parse(
            Route::has($url) ? URL::route($url) : URL::to($url)
        );
    }
}
