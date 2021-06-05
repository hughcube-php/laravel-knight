<?php

namespace HughCube\Laravel\Knight\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\PhpExecutableFinder;

class PhpIniFile extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'knight:php-ini-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the PHP INI file location';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $version = PHP_VERSION;
        $versionId = PHP_VERSION_ID;
        $this->line(
            '<info>PHP version:</info>'."<comment>{$version}</comment>"
            .'<info>, versionID:</info>'."<comment>{$versionId}</comment>"
        );

        $phpBinaryPath = (new PhpExecutableFinder())->find();
        $this->line("<info>PHP binary path:</info><comment>{$phpBinaryPath}</comment>");

        $iniFile = php_ini_loaded_file();
        $files = empty($iniFile) ? [] : [$iniFile];
        $scanFiles = php_ini_scanned_files();
        $scanFiles = explode(',', $scanFiles);
        $scanFiles = array_map('trim', $scanFiles);
        $files = array_merge($files, $scanFiles);
        $this->line('<info>PHP ini files: </info>');
        foreach ($files as $file) {
            $this->line("    <comment>$file</comment>");
        }
    }
}
