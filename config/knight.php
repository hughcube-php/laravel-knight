<?php

use HughCube\Laravel\Knight\OPcache\Commands\CompileFilesCommand;

return [
    'ping' => [
        'route_prefix' => false,
    ],

    'request' => [
        'route_prefix' => false,
    ],

    'opcache' => [
        'commands' => [
            CompileFilesCommand::class,
        ],
        'route_prefix' => false,
    ],
];
