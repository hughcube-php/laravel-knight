<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/24
 * Time: 15:55.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use GuzzleHttp\Client;
use HughCube\Laravel\Knight\Support\HttpClient;
use HughCube\Laravel\Knight\Tests\TestCase;
use ReflectionException;

class HttpClientTest extends TestCase
{
    /**
     * @throws ReflectionException
     *
     * @return void
     */
    public function testGetHttpClient()
    {
        $instance = $this->getMockForTrait(HttpClient::class);

        $this->assertInstanceOf(Client::class, $this->callMethod($instance, 'getHttpClient'));

        $this->assertSame(
            $this->callMethod($instance, 'getHttpClient'),
            $this->callMethod($instance, 'getHttpClient')
        );
    }
}
