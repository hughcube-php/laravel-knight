<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/22
 * Time: 11:18
 */

namespace HughCube\Laravel\Knight\OPcache\Commands;

use Exception;
use GuzzleHttp\RequestOptions;
use HughCube\Laravel\Knight\OPcache\LoadedOPcacheExtension;
use HughCube\Laravel\Knight\Support\HttpClient;
use HughCube\PUrl\Url;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;
use Throwable;

class CompileFilesCommand extends Command
{
    use HttpClient;
    use LoadedOPcacheExtension;

    /**
     * @inheritdoc
     */
    protected $signature = 'opcache:compile-files
                            {--with_prod_files= : Whether to include cached files on line }
                            {--with_app_files=0 : Whether to include app files }
                            {--with_composer_files=0 : Whether to include composer class files }';

    /**
     * @inheritdoc
     */
    protected $description = 'opcache compile file';

    /**
     * @param  Schedule  $schedule
     * @return void
     * @throws Exception
     */
    public function handle(Schedule $schedule)
    {
        $this->loadedOPcacheExtension();

        $scripts = $this->getFiles();
        $file = storage_path('opcache_compile_files.json');
        file_put_contents($file, json_encode($scripts));

        while (is_file($file)) {
            $process = new PhpProcess(sprintf('<?php %s ?>', $this->compileProcessCode($file)));
            $process->start();
            $process->wait(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    //echo 'ERR > '.$buffer;
                } else {
                    //echo 'OUT > '.$buffer;
                }
            });

            $remainScripts = json_decode(file_get_contents($file), true);
            if (empty($remainScripts)) {
                break;
            }
        }

        File::delete($file);

        $this->info('');
        $this->info(sprintf('opcache compile file count: %s', count($scripts)));
        $this->info('');
    }

    protected function compileProcessCode($file): string
    {
        $code = sprintf('$dataFile = "%s";', $file);
        $code .= 'while (true){';
        $code .= '    if(!is_file($dataFile)){break;}';
        $code .= '    $classmap = json_decode(file_get_contents($dataFile), true);';
        $code .= '    if(empty($classmap)){break;}';
        $code .= '    $file = array_pop($classmap);';
        $code .= '    file_put_contents($dataFile, json_encode($classmap));';
        $code .= '    if(is_file($file)){opcache_compile_file($file);};';
        $code .= '    if(is_file($file)){echo $file, PHP_EOL;}';
        $code .= '}';

        return $code;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getFiles(): array
    {
        $files = array_values(array_unique(array_merge(
            $this->getAppFiles(),
            $this->getComposerFiles(),
            $this->getProdFiles()
        )));

        foreach ($files as $index => $file) {
            if (!is_file($file)) {
                unset($files[$index]);
            }
        }

        return $files;
    }

    protected function getComposerFiles(): array
    {
        if (!$this->option('with_composer_files')) {
            return [];
        }

        $file = base_path('vendor/composer/autoload_classmap.php');
        if (!is_file($file)) {
            return [];
        }

        return array_values(require $file);
    }

    /**
     * @throws Exception
     */
    protected function getAppFiles(): array
    {
        if (!$this->option('with_app_files')) {
            return [];
        }

        $finder = Finder::create()
            ->in([
                base_path('app/'),
                base_path('bootstrap/'),
                base_path('config/'),
                base_path('public/'),
                base_path('routes/'),
                #base_path('vendor/'),
            ])
            ->name('*.php')
            ->ignoreUnreadableDirs()
            ->followLinks();

        $files = [];
        foreach ($finder->files() as $file) {
            $files[] = strval($file);
        }

        return $files;
    }

    /**
     * @return array
     */
    protected function getProdFiles(): array
    {
        if (empty($url = $this->option('with_prod_files'))) {
            return [];
        }

        $url = Url::isUrlString($url) ? $url : route($url);

        try {
            $response = $this->getHttpClient()->post($url, [
                RequestOptions::TIMEOUT => 10.0, RequestOptions::HTTP_ERRORS => false
            ]);
            $states = json_decode($response->getBody()->getContents(), true);

            $scripts = [];
            foreach (($states['data']['scripts'] ?? []) as $file) {
                $scripts[] = base_path($file);
            }
            return $scripts;
        } catch (Throwable $exception) {
        }

        return [];
    }
}
