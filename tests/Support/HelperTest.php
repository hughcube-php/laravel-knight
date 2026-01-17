<?php

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\Helper;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Exception;
use stdClass;

class HelperTest extends TestCase
{
    private function setLogPathEnv(?string $value): void
    {
        if ($value === null) {
            putenv('LOG_PATH');
            unset($_ENV['LOG_PATH'], $_SERVER['LOG_PATH']);

            return;
        }

        putenv('LOG_PATH='.$value);
        $_ENV['LOG_PATH'] = $value;
        $_SERVER['LOG_PATH'] = $value;
    }

    public function testAssertClassExists()
    {
        $helper = new Helper();
        
        $helper->assertClassExists(stdClass::class);
        $this->assertTrue(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Class 'NonExistentClass' does not exist.");
        $helper->assertClassExists('NonExistentClass');
    }

    public function testAssertLoadedExtension()
    {
        $helper = new Helper();

        // Standard PHP extension that should be loaded
        $helper->assertLoadedExtension('json');
        $this->assertTrue(true);

        $this->expectException(RuntimeException::class);
        $helper->assertLoadedExtension('non_existent_extension_xyz');
    }

    public function testConvertExceptionToArray()
    {
        $exception = new Exception('Test message', 123);
        $array = Helper::convertExceptionToArray($exception);

        $this->assertSame(123, $array['code']);
        $this->assertSame(Exception::class, $array['exception']);
        $this->assertSame('Test message', $array['message']);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('trace', $array);
        $this->assertArrayNotHasKey('previous', $array);
    }

    public function testConvertExceptionToArrayWithPrevious()
    {
        $previous = new Exception('Previous message', 456);
        $exception = new Exception('Test message', 123, $previous);
        
        $array = Helper::convertExceptionToArray($exception);

        $this->assertArrayHasKey('previous', $array);
        $this->assertSame('Previous message', $array['previous']['message']);
        $this->assertSame(456, $array['previous']['code']);
    }

    public function testConvertExceptionToArrayWithValidationException()
    {
        $validator = \Illuminate\Support\Facades\Validator::make([], []);
        $exception = new ValidationException($validator);
        
        $array = Helper::convertExceptionToArray($exception);
        
        $this->assertArrayHasKey('errors', $array);
        $this->assertSame($exception->errors(), $array['errors']);
    }

    public function testLogPathUsesLoggingConfig()
    {
        $original = getenv('LOG_PATH');

        try {
            $this->setLogPathEnv('env_logs');
            config(['logging.path' => 'logs_base']);
            config(['app.log_path' => 'app_logs']);

            $this->assertSame('logs_base', log_path());
            $this->assertSame('logs_base'.DIRECTORY_SEPARATOR.'app.log', log_path('app.log'));
        } finally {
            $this->setLogPathEnv($original === false ? null : $original);
        }
    }

    public function testLogPathUsesEnvWhenNoLoggingConfig()
    {
        $original = getenv('LOG_PATH');

        try {
            $this->setLogPathEnv('env_logs');
            config(['logging.path' => null]);
            config(['app.log_path' => 'app_logs']);

            $this->assertSame('env_logs', log_path());
            $this->assertSame('env_logs'.DIRECTORY_SEPARATOR.'sub/dir', log_path('/sub/dir'));
        } finally {
            $this->setLogPathEnv($original === false ? null : $original);
        }
    }

    public function testLogPathUsesAppLogPathWhenNoLoggingOrEnv()
    {
        $original = getenv('LOG_PATH');

        try {
            $this->setLogPathEnv(null);
            config(['logging.path' => null]);
            config(['app.log_path' => 'app_logs']);

            $this->assertSame('app_logs', log_path());
        } finally {
            $this->setLogPathEnv($original === false ? null : $original);
        }
    }

    public function testLogPathFallsBackToStoragePath()
    {
        $original = getenv('LOG_PATH');

        try {
            $this->setLogPathEnv(null);
            config(['logging.path' => null]);
            config(['app.log_path' => null]);

            $this->assertSame(storage_path('logs'), log_path());
        } finally {
            $this->setLogPathEnv($original === false ? null : $original);
        }
    }
}
