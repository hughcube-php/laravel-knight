<?php

use HughCube\Laravel\Knight\Http\Actions\PingAction;
use HughCube\Laravel\Knight\OPcache\Actions\ScriptsAction;
use HughCube\Laravel\Knight\OPcache\Actions\StatesAction;
use HughCube\Laravel\Knight\OPcache\Commands\CompileFilesCommand;

return [
    'ping' => [
        'routes' => [
            [
                'uri' => 'ping',
                'name' => 'ping',
                'action' => PingAction::class,
            ],
        ],
    ],

    'request' => [
        'routes' => [
            [
                'uri' => 'ping',
                'name' => 'ping',
                'action' => PingAction::class,
            ],
        ],
    ],

    'opcache' => [
        'commands' => [
            CompileFilesCommand::class,
        ],
        'routes' => [
            [
                'uri' => 'opcache/scripts',
                'name' => 'opcache_scripts',
                'action' => ScriptsAction::class,
            ],
            [
                'uri' => 'opcache/states',
                'name' => 'opcache_states',
                'action' => StatesAction::class,
            ],
        ],
    ],
];
