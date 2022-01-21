<?php

return [
    'ping' => [
        'routes' => [
            [
                'uri'    => 'ping',
                'name'   => 'ping',
                'action' => \HughCube\Laravel\Knight\Http\Actions\PingAction::class,
            ],
        ],
    ],

    'request' => [
        'routes' => [
            [
                'uri'    => 'ping',
                'name'   => 'ping',
                'action' => \HughCube\Laravel\Knight\Http\Actions\PingAction::class,
            ],
        ],
    ],

    'opcache' => [
        'commands' => [
            \HughCube\Laravel\Knight\OPcache\Commands\CompileFilesCommand::class,
        ],
        'routes' => [
            [
                'uri'    => 'opcache/scripts',
                'name'   => 'opcache_scripts',
                'action' => \HughCube\Laravel\Knight\OPcache\Actions\ScriptsAction::class,
            ],
            [
                'uri'    => 'opcache/states',
                'name'   => 'opcache_states',
                'action' => \HughCube\Laravel\Knight\OPcache\Actions\StatesAction::class,
            ],
        ],
    ],
];
