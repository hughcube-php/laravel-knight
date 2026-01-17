<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\QueueFlowJob;
use HughCube\Laravel\Knight\Tests\TestCase;

class QueueFlowJobStub extends QueueFlowJob
{
    public array $idsQueue = [];
    public array $logs = [];

    protected function nextAvailableJob(): ?string
    {
        $value = array_shift($this->idsQueue);

        return $value ?: null;
    }

    public function log($level, string $message, array $context = []): void
    {
        $this->logs[] = [$level, $message];
    }
}

class QueueFlowJobTest extends TestCase
{
    public function testActionStopsWhenNoJobs()
    {
        $job = new QueueFlowJobStub([
            'from_connection' => 'sync',
            'to_connection' => 'sync',
            'max_time' => 1,
        ]);
        $job->idsQueue = ['job1', 'job2', null];

        $this->callMethod($job, 'loadParameters');
        $this->callMethod($job, 'action');

        $this->assertNotEmpty($job->logs);
        $this->assertStringContainsString('推送任务2个', $job->logs[0][1]);
    }

    public function testGetMaxTimeUsesDefaultAndProvidedValue()
    {
        $job = new QueueFlowJobStub([
            'from_connection' => 'sync',
            'to_connection' => 'sync',
        ]);
        $this->callMethod($job, 'loadParameters');
        $this->assertSame(60, $this->callMethod($job, 'getMaxTime'));

        $job = new QueueFlowJobStub([
            'from_connection' => 'sync',
            'to_connection' => 'sync',
            'max_time' => 5,
        ]);
        $this->callMethod($job, 'loadParameters');
        $this->assertSame(5, $this->callMethod($job, 'getMaxTime'));
    }

    public function testParseJobReturnsUnserializedObject()
    {
        $job = new QueueFlowJobStub([
            'from_connection' => 'sync',
            'to_connection' => 'sync',
        ]);

        $payload = json_encode([
            'data' => [
                'command' => serialize(new \stdClass()),
            ],
        ]);

        $parsed = $this->callMethod($job, 'parseJob', [$payload]);

        $this->assertInstanceOf(\stdClass::class, $parsed);
    }
}
