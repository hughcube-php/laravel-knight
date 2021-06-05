<?php

namespace HughCube\Laravel\Knight\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\VarDumper\VarDumper;

class Config extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'knight:config
                        {key="": Application config name }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the Application config';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->argument("key");

        VarDumper::dump(config((empty($key) ? null : $key)));
    }
}
