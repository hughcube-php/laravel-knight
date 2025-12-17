<?php

namespace HughCube\Laravel\Knight\Tests\Routing;

use GuzzleHttp\Client;
use HughCube\Laravel\Knight\Routing\Action;
use HughCube\Laravel\Knight\Routing\ProxyForwarder;

class ProxyForwarderAction
{
    use Action;
    use ProxyForwarder;

    protected $externalClient;

    public function setClient(Client $client)
    {
        $this->externalClient = $client;
    }

    public function getHttpClient(): Client
    {
        return $this->externalClient ?? new Client();
    }

    protected function getProxyUrl(): string
    {
        return 'http://example.com';
    }

    protected function rules(): array
    {
        return [];
    }
}
