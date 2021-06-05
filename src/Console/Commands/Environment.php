<?php

namespace HughCube\Laravel\Knight\Console\Commands;

use Illuminate\Console\Command;

class Environment extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'knight:env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the current framework environment';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->line(
            '<info>Current application environment:</info> <comment>' . $this->laravel->environment() . '</comment>'
        );
    }
}
