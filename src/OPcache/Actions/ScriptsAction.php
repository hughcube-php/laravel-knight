<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\Knight\OPcache\Actions;

use Exception;
use HughCube\Laravel\Knight\OPcache\LoadedOPcacheExtension;
use HughCube\Laravel\Knight\Routing\Controller;
use HughCube\Laravel\Knight\Support\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ScriptsAction extends Controller
{
    use LoadedOPcacheExtension;

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     *
     * @return Response
     */
    protected function action(): Response
    {
        $this->loadedOPcacheExtension();

        $scripts = array_merge(
            $this->getHistoryScripts(),
            $this->getScripts()
        );

        /** Scripts that have not been used for 30 days are considered invalid */
        foreach ($scripts as $file => $time) {
            if ((30 * 24 * 3600) < (time() - $time)) {
                unset($scripts[$file]);
            }
        }

        $this->getCache()->set($this->getCacheKey(), $scripts, Carbon::now()->addYears());

        return $this->asResponse([
            'count'   => count($scripts),
            'scripts' => array_keys($scripts),
        ]);
    }

    protected function getScripts(): array
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
     * @throws InvalidArgumentException
     *
     * @return array
     */
    protected function getHistoryScripts(): array
    {
        $histories = $this->getCache()->get($this->getCacheKey());

        return is_array($histories) ? $histories : [];
    }

    protected function getCacheKey(): string
    {
        $string = serialize(['v1.0.1', __METHOD__]);

        return sprintf('%s%s', md5($string), crc32($string));
    }

    /**
     * @return Repository
     */
    protected function getCache(): Repository
    {
        return Cache::store();
    }
}
