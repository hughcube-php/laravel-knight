<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/2/7
 * Time: 15:37
 */

namespace HughCube\Laravel\Knight\OPcache;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\Laravel\Knight\Support\Str;
use HughCube\Laravel\Knight\Traits\Container;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use Psr\SimpleCache\InvalidArgumentException;

class OPcache
{
    use Container;
    use HttpClientTrait;
    use LoadedOPcacheExtension;

    public static function i(): OPcache
    {
        /** @phpstan-ignore-next-line */
        return new static;
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getScripts(): array
    {
        $this->loadedOPcacheExtension();

        $scripts = array_merge($this->getHistoryScripts(), $this->getScripts());

        /** Scripts that have not been used for 30 days are considered invalid */
        foreach ($scripts as $file => $time) {
            if ((365 * 24 * 3600) < (time() - $time)) {
                unset($scripts[$file]);
            }
        }

        $this->getCache()->set($this->getCacheKey(), $scripts, Carbon::now()->addYears());

        return $scripts;
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws GuzzleException
     */
    public function getRemoteScripts($url = null, $timeout = 5, $useAppHost = true): array
    {
        $url = $this->getUrl($url);
        if (!$url instanceof PUrl) {
            throw new Exception('Remote interface URL cannot be found!');
        }

        /** 替换域名为 app.url */
        if ($useAppHost
            && (
                Str::isIp($url->getHost())
                || $url->matchHost('localhost')
                || $url->matchHost('127.0.0.1')
            )
        ) {
            $appUrl = PUrl::parse($this->getContainerConfig()->get('app.url'));
            if ($appUrl instanceof PUrl) {
                $url = $url->withHost($appUrl->getHost())
                    ->withPort($appUrl->getPort())
                    ->withScheme($appUrl->getScheme());
            }
        }

        /** 发送请求 */
        $response = $this->getHttpClient()->get($url, [
            RequestOptions::TIMEOUT => floatval($timeout),
            RequestOptions::ALLOW_REDIRECTS => ['max' => 5, 'referer' => true, 'track_redirects' => true],
        ]);

        $results = json_decode($response->getBody()->getContents(), true);

        return $results['data']['scripts'];
    }

    public function getUrl($url = null): ?PUrl
    {
        $url = $url ?? 'knight.opcache.scripts';

        if (($uri = PUrl::parse($url)) instanceof PUrl) {
            return $uri;
        }

        return PUrl::parse(
            Route::has($url) ? URL::route($url) : URL::to($url)
        );
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getHistoryScripts(): array
    {
        $histories = $this->getCache()->get($this->getCacheKey());
        return is_array($histories) ? $histories : [];
    }

    protected function getOpCacheScripts(): array
    {
        if (!function_exists('opcache_get_status')) {
            return [];
        }

        $status = opcache_get_status();

        $scripts = [];
        foreach (($status['scripts'] ?? []) as $script) {
            $file = ltrim(Str::replaceFirst(base_path(), '', $script['full_path']), '/');
            $scripts[$file] = time();
        }

        return $scripts;
    }

    /**
     * @param  array<Stmt>  $stmts
     */
    public function getPHPParserStmtClasses(array $stmts, $namespace = null): Collection
    {
        $classes = Collection::make();

        foreach ($stmts as $stmt) {

            /** class */
            if ($stmt instanceof Class_) {
                if ($stmt->name instanceof Identifier) {
                    if (empty($namespace)) {
                        $classes = $classes->add($stmt->name->name);
                    } else {
                        $classes = $classes->add(sprintf('%s\%s', $namespace, $stmt->name->name));
                    }
                }
            }

            /** namespace */
            if ($stmt instanceof Namespace_) {
                $classes = $classes->merge(
                    $this->getPHPParserStmtClasses($stmt->stmts, $stmt->name)
                );
            }
        }

        return $classes->unique()->values();
    }

    protected function getCacheKey(): string
    {
        $string = serialize(['v1.0.2', __METHOD__]);
        return sprintf('%s%s', md5($string), crc32($string));
    }

    public function getCache(): Repository
    {
        return Cache::store();
    }
}
