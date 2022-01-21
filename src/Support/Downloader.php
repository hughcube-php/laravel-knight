<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 16:09.
 */

namespace HughCube\Laravel\Knight\Support;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\StaticInstanceInterface;
use HughCube\StaticInstanceTrait;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * @method static void save(string $url, string $file, array $options = [])
 */
class Downloader implements StaticInstanceInterface
{
    use HttpClient;
    use StaticInstanceTrait;

    /**
     * @param string $url
     *
     * @return string
     */
    public static function path(string $url): string
    {
        $extension = File::extension($url);

        return storage_path(sprintf(
            'download/%s/%s/%s%s',
            substr(md5($url), 0, 2),
            substr(md5(microtime()), 0, 2),
            md5($url),
            ($extension ? ".{$extension}" : '')
        ));
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $file
     * @param array  $options
     *
     * @throws GuzzleException
     *
     * @return void
     */
    private function to(string $method, string $url, string $file, array $options = [])
    {
        if (!File::exists(dirname($file))) {
            File::makeDirectory(dirname($file), 0777, true);
        }

        $this->getHttpClient()->request($method, $url, array_merge(
            [RequestOptions::SINK => $file, RequestOptions::TIMEOUT => 120],
            $options
        ));
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @throws GuzzleException
     *
     * @return void
     */
    public function __call(string $method, array $args)
    {
        array_unshift($args, ('SAVE' === strtoupper($method) ? 'GET' : $method));
        $this->to(...$args);
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array  $args
     *
     * @throws RuntimeException
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        return static::instance()->$method(...$args);
    }
}
