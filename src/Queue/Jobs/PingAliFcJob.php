<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use GuzzleHttp\Exception\GuzzleException;
use HughCube\Laravel\AliFC\AliFC;
use HughCube\Laravel\AliFC\Client;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface as Response;

class PingAliFcJob extends PingJob
{
    #[ArrayShape([])]
    public function rules(): array
    {
        return [
            'client'          => ['nullable'],
            'url'             => ['string', 'nullable', 'default:alifc_ping'],
            'method'          => ['string', 'default:GET'],
            'timeout'         => ['integer', 'default:2'],
            'allow_redirects' => ['integer', 'default:0'],
        ];
    }

    /**
     * @throws GuzzleException
     */
    protected function request(string $method, $uri = '', array $options = []): Response
    {
        return $this->getAliFcClient()->request($method, $uri, $options);
    }

    /**
     * @return Client
     */
    protected function getAliFcClient(): Client
    {
        return AliFC::client(($this->get('client') ?: null));
    }
}
