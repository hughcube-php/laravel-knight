<?php

namespace HughCube\Laravel\Knight\Tests\Database;

use Exception;
use HughCube\Laravel\Knight\Database\DB;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\QueryException;

class DbTest extends TestCase
{
    public function testRetryOnQueryExceptionReturnsValueAfterRetries()
    {
        $attempts = 0;

        $result = DB::retryOnQueryException(function () use (&$attempts) {
            $attempts++;

            if ($attempts < 3) {
                throw new QueryException('sqlite', 'select 1', [], new Exception('fail'));
            }

            return 'ok';
        }, 3, 0);

        $this->assertSame('ok', $result);
        $this->assertSame(3, $attempts);
    }

    public function testRetryOnQueryExceptionThrowsAfterLimit()
    {
        $attempts = 0;

        try {
            DB::retryOnQueryException(function () use (&$attempts) {
                $attempts++;

                throw new QueryException('sqlite', 'select 1', [], new Exception('fail'));
            }, 2, 0);

            $this->fail('Expected QueryException was not thrown.');
        } catch (QueryException $exception) {
            $this->assertSame(2, $attempts);
            $this->assertStringContainsString('select 1', $exception->getMessage());
        }
    }
}
