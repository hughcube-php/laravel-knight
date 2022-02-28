<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 16:09.
 */

namespace HughCube\Laravel\Knight\Support;

use BadMethodCallException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\StaticInstanceInterface;
use HughCube\StaticInstanceTrait;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * @method static string save(string $url, string $file = null, array $options = [])
 * @method static string get(string $url, string $file = null, array $options = [])
 * @method static string post(string $url, string $file = null, array $options = [])
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
     * @param string      $method
     * @param string      $url
     * @param null|string $file
     * @param array       $options
     *
     * @throws GuzzleException
     * @throws Exception
     *
     * @return string
     */
    public function to(string $method, string $url, ?string $file = null, array $options = []): string
    {
        $file = $file ?: $this->path($url);
        if (!File::exists(dirname($file))) {
            File::makeDirectory(dirname($file), 0777, true);
        }

        $response = $this->getHttpClient()->request($method, $url, array_merge(
            [RequestOptions::SINK => $file, RequestOptions::TIMEOUT => 120],
            $options
        ));

        if (200 <= $response->getStatusCode() && 300 > $response->getStatusCode()) {
            return $file;
        }

        throw new Exception('File download failure.');
    }

    /**
     * @param string $name
     * @param array  $args
     *
     * @throws GuzzleException
     *
     * @return string
     */
    public function __call(string $name, array $args): string
    {
        if ('save' === strtolower($name)) {
            return $this->get(...$args);
        }

        if (in_array(strtoupper($name), ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])) {
            array_unshift($args, strtoupper($name));

            return $this->to(...$args);
        }

        throw new BadMethodCallException("No such method exists: {$name}");
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
        return call_user_func_array([static::instance(), $method], $args);
    }
}
