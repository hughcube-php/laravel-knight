<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use GuzzleHttp\Psr7\Response;
use HughCube\Laravel\Knight\Queue\Jobs\PingJob;
use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Facades\URL;

class PingJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(PingJob::new());
    }

    public function testGetRequestIdReadsHeader()
    {
        $job = PingJob::new();

        $response = new Response(200, ['X-Request-Id' => 'req-123']);

        $this->assertSame('req-123', $this->callMethod($job, 'getRequestId', [$response]));
        $this->assertNull($this->callMethod($job, 'getRequestId', [null]));
    }

    public function testGetUrlReturnsAbsoluteUrl()
    {
        $job = new PingJob(['url' => 'https://example.test/ping']);

        $url = $this->callMethod($job, 'getUrl');

        $this->assertSame('https://example.test/ping', $url);
    }

    public function testGetUrlUsesAppSchemeForRelativePath()
    {
        $job = new PingJob(['url' => 'health']);

        config(['app.url' => 'https://example.test']);
        URL::forceRootUrl('http://127.0.0.1');

        try {
            $url = $this->callMethod($job, 'getUrl');
            $parsed = PUrl::parse($url);

            $this->assertInstanceOf(PUrl::class, $parsed);
            $this->assertSame('https', $parsed->getScheme());
        } finally {
            URL::forceRootUrl(null);
        }
    }
}
