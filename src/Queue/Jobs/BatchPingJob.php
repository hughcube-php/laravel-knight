<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Exception;
use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Traits\Container;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface as Response;

class BatchPingJob extends Job
{
    use HttpClientTrait;
    use Container;

    public function rules(): array
    {
        return [
            'jobs' => ['array', 'min:1'],

            'jobs.*.url'             => ['string', 'nullable'],
            'jobs.*.method'          => ['string', 'nullable'],
            'jobs.*.timeout'         => ['integer', 'nullable'],
            'jobs.*.allow_redirects' => ['integer', 'nullable'],
        ];
    }

    /**
     * @throws Exception
     */
    protected function action(): void
    {
        $requests = [];
        $responses = [];

        /** 组建请求 */
        foreach ($this->p('jobs', []) as $index => $job) {
            $requests[$index] = [
                'url'             => $this->parseUrl($job['url'] ?? 'knight.ping'),
                'method'          => strtoupper($job['method'] ?? null ?: 'GET'),
                'timeout'         => $job['timeout'] ?? null ?: 2,
                'allow_redirects' => $this->parseAllowRedirects($job['allow_redirects'] ?? null ?: 0),
            ];
        }

        /** 发送请求 */
        $start = microtime(true);
        foreach ($requests as $index => $request) {
            $responses[$index] = $this->getHttpClient()->requestLazy($request['method'], $request['url'], [
                RequestOptions::HTTP_ERRORS     => false,
                RequestOptions::TIMEOUT         => $request['timeout'],
                RequestOptions::ALLOW_REDIRECTS => $request['allow_redirects'],
            ]);
        }

        /** 等待响应 */
        foreach ($responses as $index => $response) {
            $statusCode = $response->getStatusCode();
            $requestId = $this->parseRequestId($response);

            $end = microtime(true);

            $url = $requests[$index]['url'];
            $method = $requests[$index]['method'];
            $duration = round(($end - $start) * 1000, 2);

            $this->info(sprintf('%sms [%s] [%s] %s %s', $duration, $requestId, $statusCode, $method, $url));
        }
    }

    /**
     * @throws Exception
     */
    protected function parseUrl($url): string
    {
        if (PUrl::isUrlString($url)) {
            return $url;
        }

        $url = Route::has($url) ? route($url) : URL::to($url);

        /** parse url */
        $purl = PUrl::parse($url);
        if (!$purl instanceof PUrl) {
            throw new Exception('The requested url must be correct!');
        }

        /** with app url scheme */
        $appUrl = PUrl::parse($this->getContainerConfig()->get('app.url'));
        if ($appUrl instanceof PUrl && !empty($appUrl->getScheme())) {
            $purl = $purl->withScheme($appUrl->getScheme());
        }

        return $purl->toString();
    }

    /**
     * @return array|false
     */
    protected function parseAllowRedirects($redirects)
    {
        if (0 >= $redirects) {
            return false;
        }

        return [
            'max'       => $redirects,
            'strict'    => true,
            'referer'   => true,
            'protocols' => ['https', 'http'],
        ];
    }

    protected function parseRequestId($response): ?string
    {
        if (!$response instanceof Response) {
            return null;
        }

        foreach ($response->getHeaders() as $name => $header) {
            if (Str::endsWith(strtolower($name), 'request-id')) {
                return $response->getHeaderLine($name);
            }
        }

        return null;
    }
}
