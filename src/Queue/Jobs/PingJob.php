<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Exception;
use GuzzleHttp\RequestOptions;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Support\HttpClient;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface as Response;
use Throwable;

class PingJob extends Job
{
    use HttpClient;

    #[ArrayShape([])]
    public function rules(): array
    {
        return [
            'url' => ['string', 'nullable'],
            'method' => ['string', 'default:GET'],
            'timeout' => ['integer', 'default:2'],
            'allow_redirects' => ['integer', 'default:0'],
        ];
    }

    /**
     * @throws Exception
     */
    protected function action(): void
    {
        $url = $this->getUrl();
        $method = strtoupper($this->get('method'));
        $timeout = $this->get('timeout');

        $response = null;
        $exception = null;

        $start = microtime(true);
        try {
            $response = $this->getHttpClient()->request($method, $url, [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::TIMEOUT => $timeout,
                RequestOptions::ALLOW_REDIRECTS => $this->getAllowRedirects(),
            ]);
        } catch (Throwable $exception) {
        }
        $end = microtime(true);

        $duration = round(($end - $start) * 1000, 2);
        $requestId = $this->getRequestId($response);
        $statusCode = $response instanceof Response ? $response->getStatusCode() : null;
        $exception = $exception instanceof Throwable ? sprintf('exception:%s', $exception->getMessage()) : null;

        $this->info(sprintf('%sms [%s] [%s] %s %s %s', $duration, $requestId, $statusCode, $method, $url, $exception));
    }

    protected function getRequestId($response): ?string
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

    protected function getUrl(): string
    {
        $url = $this->get('url', 'knight_ping');
        if (is_string($url) && PUrl::isUrlString($url)) {
            return $url;
        }

        /** get app route */
        $url = Route::has($url) ? route($url) : URL::to($url);

        /** parse url */
        $purl = PUrl::parse($url);

        /** with app url scheme */
        $appUrl = PUrl::parse(config('app.url'));
        if ($appUrl instanceof PUrl && !empty($appUrl->getScheme()) && $purl instanceof PUrl) {
            $purl = $purl->withScheme($appUrl->getScheme());
        }

        return $purl instanceof PUrl ? $purl->toString() : $url;
    }

    protected function getAllowRedirects(): bool|array
    {
        if (0 >= ($redirects = intval($this->get('allow_redirects', 0)))) {
            return false;
        }

        return [
            'max' => $redirects,
            'strict' => true,
            'referer' => true,
            'protocols' => ['https', 'http']
        ];
    }
}
