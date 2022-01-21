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
    protected $signature = 'knight:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the Application config';

    public function configure()
    {
        parent::configure();

        $this->addArgument('key', null, 'config key, Default output all', '');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->argument('key');

        VarDumper::dump(config((empty($key) ? null : $key)));
    }
}
