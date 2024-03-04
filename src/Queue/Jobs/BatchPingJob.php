<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Carbon\Carbon;
use Exception;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Traits\Container;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface as Response;
use Throwable;

class BatchPingJob extends Job
{
    use HttpClientTrait;
    use Container;

    public function rules(): array
    {
        return [
            'concurrency' => ['integer', 'nullable'],

            'jobs' => ['array', 'min:1'],

            'jobs.*.url'             => ['string', 'nullable'],
            'jobs.*.method'          => ['string', 'nullable'],
            'jobs.*.timeout'         => ['integer', 'nullable'],
            'jobs.*.allow_redirects' => ['integer', 'nullable'],
            'jobs.*.headers'         => ['array', 'nullable'],
        ];
    }

    /**
     * @throws Exception
     */
    protected function action(): void
    {
        $requests = [];
        foreach ($this->p('jobs', []) as $index => $job) {
            $requests[$index] = [
                'url'             => $this->parseUrl($job['url'] ?? 'knight.ping'),
                'method'          => strtoupper($job['method'] ?? null ?: 'GET'),
                'headers'         => $job['headers'] ?? [] ?: [],
                'timeout'         => $job['timeout'] ?? null ?: 2.0,
                'allow_redirects' => $this->parseAllowRedirects($job['allow_redirects'] ?? null ?: 0),
            ];
        }

        $start = Carbon::now();
        $pool = new Pool(new Client(), $this->makeRequests($requests), [
            'concurrency' => $this->p('concurrency') ?: 5,
            'fulfilled'   => function (Response $response, $index) use ($requests, $start) {
                $url = $requests[$index]['url'];
                $method = $requests[$index]['method'];
                $duration = Carbon::now()->diffInMilliseconds($start);
                $this->logResponse($method, $url, $duration, $response);
            },
            'rejected' => function ($reason, $index) use ($requests, $start) {
                $url = $requests[$index]['url'];
                $method = $requests[$index]['method'];
                $duration = Carbon::now()->diffInMilliseconds($start);
                /** parse response */
                $response = null;
                if (is_object($reason) && method_exists($reason, 'getResponse')) {
                    $response = $reason->getResponse();
                }

                /** log response */
                if ($response instanceof Response) {
                    $this->logResponse($method, $url, $duration, $reason->getResponse());
                }//
                /** log exception */
                elseif ($reason instanceof Throwable) {
                    $this->info(sprintf(
                        'method: %s, url: %s, status: %s, duration: %sms requestId: %s, exception: %s',
                        $method,
                        $url,
                        '',
                        $duration,
                        '',
                        $reason->getMessage()
                    ));
                }//
                /** log unknown */
                else {
                    $this->info(sprintf(
                        'method: %s, url: %s, status: %s, duration: %sms requestId: %s, exception: %s',
                        $method,
                        $url,
                        '',
                        $duration,
                        '',
                        get_debug_type($reason)
                    ));
                }
            },
        ]);

        $pool->promise()->wait();
    }

    protected function logResponse($method, $url, $duration, Response $response)
    {
        $statusCode = $response->getStatusCode();
        $requestId = $this->parseRequestId($response);

        $this->info(sprintf(
            'method: %s, url: %s, status: %s, duration: %sms requestId: %s, exception: %s',
            $method,
            $url,
            $statusCode,
            $duration,
            $requestId,
            ''
        ));
    }

    /**
     * @throws Exception
     */
    protected function makeRequests($requests): Generator
    {
        foreach ($requests as $request) {
            yield new Request($request['method'], $request['url'], array_merge(
                [
                    RequestOptions::HTTP_ERRORS     => false,
                    RequestOptions::TIMEOUT         => $request['timeout'],
                    RequestOptions::ALLOW_REDIRECTS => $request['allow_redirects'],
                ],
                array_filter([
                    RequestOptions::HEADERS         => $request['headers'] ?? [] ?: [],
                ])
            ));
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
        $appUrl = PUrl::parse($this->getContainerConfig('app.url'));
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
