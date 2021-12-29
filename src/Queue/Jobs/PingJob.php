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
                RequestOptions::TIMEOUT => $timeout
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

    protected function getUrl()
    {
        $url = $this->get('url', 'knight_ping');
        if (PUrl::isUrlString($url)) {
            return $url;
        }

        if (Route::has($url)) {
            return route($url);
        }

        return URL::to($url);
    }
}
