<?php

/**
 * OPcache Preload Configuration Example.
 *
 * Copy this file to your project root as 'preload-config.php' to customize preload behavior.
 */

return [
    /**
     * 排除特定的类/接口/Trait
     * 这些类不会被写入 preload.php.
     */
    'exclude_classes' => [
        // 添加你想排除的类名
        // 例如: 'App\SomeProblematicClass',

        // 示例: 排除测试相关的类
        // 'Tests\TestCase',
        // 'Tests\CreatesApplication',
    ],
];
