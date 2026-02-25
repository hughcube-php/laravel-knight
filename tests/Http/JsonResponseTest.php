<?php

namespace HughCube\Laravel\Knight\Tests\Http;

use HughCube\Laravel\Knight\Http\JsonResponse;
use HughCube\Laravel\Knight\Tests\TestCase;

class JsonResponseTest extends TestCase
{
    public function testUsesDefaultEncodingOptionsWhenOptionsAreZero(): void
    {
        $response = new JsonResponse(['ok' => true], 200, [], 0);

        $this->assertSame(
            $this->expectedOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS),
            $response->getEncodingOptions()
        );
    }

    public function testKeepsCustomEncodingOptionsWhenOptionsAreProvided(): void
    {
        $customOptions = JSON_UNESCAPED_UNICODE;
        $response = new JsonResponse(['name' => '测试'], 200, [], $customOptions);

        $this->assertSame(
            $this->expectedOptions($customOptions),
            $response->getEncodingOptions()
        );
    }

    public function testCanEncodeInvalidUtf8Input(): void
    {
        $response = new JsonResponse(['bad' => hex2bin('B131')], 200, [], 0);
        $content = $response->getContent();

        $this->assertNotFalse($content);
        $this->assertIsArray(json_decode($content, true));
    }

    private function expectedOptions(int $options): int
    {
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $options |= JSON_INVALID_UTF8_SUBSTITUTE;
        } elseif (defined('JSON_INVALID_UTF8_IGNORE')) {
            $options |= JSON_INVALID_UTF8_IGNORE;
        }

        return $options;
    }
}
