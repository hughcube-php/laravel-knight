<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/22
 * Time: 11:18.
 */

namespace HughCube\Laravel\Knight\OPcache\Commands;

use Exception;
use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\Laravel\Knight\OPcache\LoadedOPcacheExtension;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\PhpProcess;
use Throwable;

class CompileFilesCommand extends Command
{
    use HttpClientTrait;
    use LoadedOPcacheExtension;

    /**
     * @inheritdoc
     */
    protected $signature = 'opcache:compile-files
                            {--with_remote_cached_scripts }
                            {--with_app_files : Whether to include app files }
                            {--with_composer_files : Whether to include composer class files }';

    /**
     * @inheritdoc
     */
    protected $description = 'opcache compile file';

    /**
     * @param  Schedule  $schedule
     *
     * @return void
     * @throws Exception
     *
     */
    public function handle(Schedule $schedule)
    {
        $start = Carbon::now();

        $scripts = $this->getFiles();
        $file = storage_path('opcache_compile_files.json');
        file_put_contents($file, json_encode($scripts));
        while (is_file($file)) {
            $process = new PhpProcess(sprintf('<?php %s ?>', $this->compileProcessCode($file)));
            $process->start();
            $process->wait();
            $remainScripts = json_decode(file_get_contents($file), true);
            if (empty($remainScripts)) {
                break;
            }
        }
        File::delete($file);

        $end = Carbon::now();

        $this->info(sprintf(
            'opcache compile file count: %s, duration: %ss',
            count($scripts),
            /** @phpstan-ignore-next-line */
            $end->getTimestampAsFloat() - $start->getTimestampAsFloat()
        ));
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
     *
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

        $files = [];

        $file = base_path('vendor/composer/autoload_classmap.php');
        $files = array_merge($files, array_values(is_file($file) ? require $file : []));

        $file = base_path('vendor/composer/autoload_files.php');
        $files = array_merge($files, array_values(is_file($file) ? require $file : []));

        return array_values(array_filter(array_unique($files)));
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
                //base_path('config/'),
                //base_path('public/'),
                //base_path('routes/'),
                //base_path('vendor/'),
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

    protected function getProdFiles(): array
    {
        if (empty($url = $this->option('with_remote_cached_scripts'))) {
            return [];
        }

        /** @phpstan-ignore-next-line */
        if (true === $url || '1' === $url || 1 === $url) {
            $url = 'knight.opcache.scripts';
        }

        if (!PUrl::isUrlString($url) && Route::has($url)) {
            $url = route($url);
        }

        if (!PUrl::isUrlString($url)) {
            $message = 'Description Failed to run the opcache:compile-files command, ';
            Log::info($message.'Remote interface URL cannot be found!');

            return [];
        }

        try {
            $response = $this->getHttpClient()->post($url, [
                RequestOptions::TIMEOUT => 10.0,
                RequestOptions::HTTP_ERRORS => false,
            ]);
            $states = json_decode($response->getBody()->getContents(), true);

            $scripts = [];
            foreach (($states['data']['scripts'] ?? []) as $file) {
                $scripts[] = base_path($file);
            }

            return $scripts;
        } catch (Throwable $exception) {
            $this->warn($exception->getMessage());
        }

        return [];
    }
}
