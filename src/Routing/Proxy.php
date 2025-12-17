<?php

namespace HughCube\Laravel\Knight\Routing;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\HttpClientTrait;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @mixin Action
 */
trait Proxy
{
    use HttpClientTrait;

    /**
     * @return Response
     * @throws GuzzleException
     */
    protected function action(): Response
    {
        $response = $this->getHttpClient()->request(
            $this->getProxyMethod(),
            $this->getProxyUrl(),
            $this->getProxyRequestOptions()
        );

        return new StreamedResponse(function () use ($response) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            while (!$body->eof()) {
                echo $body->read(1024 * 8);
            }
        }, $response->getStatusCode(), Collection::make($response->getHeaders())->filter(fn($v, $k) => !$this->isIgnoreProxyHeader(strval($k)))->all());
    }

    /**
     * @return string
     */
    abstract protected function getProxyUrl(): string;

    /**
     * @return string
     */
    protected function getProxyMethod(): string
    {
        return $this->getRequest()->getMethod();
    }

    /**
     * @return array
     */
    protected function getProxyRequestOptions(): array
    {
        return [
            RequestOptions::HEADERS => $this->getProxyHeaders(),
            RequestOptions::BODY => $this->getProxyBody(),
            RequestOptions::QUERY => $this->getProxyQuery(),
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::STREAM => true,
        ];
    }

    /**
     * @return false|resource|string|null
     */
    protected function getProxyBody()
    {
        return $this->getRequest()->getContent();
    }

    /**
     * @return array
     */
    protected function getProxyQuery(): array
    {
        return $this->getRequest()->query->all();
    }

    /**
     * @return array
     */
    protected function getProxyHeaders(): array
    {
        $headers = $this->getRequest()->headers->all();

        foreach ($headers as $key => $values) {
            if ($this->isIgnoreProxyHeader($key)) {
                unset($headers[$key]);
            }
        }

        return $headers;
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function isIgnoreProxyHeader(string $name): bool
    {
        return in_array(strtolower($name), ['host', 'content-length', 'transfer-encoding', 'connection']);
    }
}
