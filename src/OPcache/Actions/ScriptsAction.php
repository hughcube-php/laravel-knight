<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午
 */

namespace HughCube\Laravel\Knight\OPcache\Actions;

use Exception;
use HughCube\Laravel\Knight\OPcache\LoadedOPcacheExtension;
use HughCube\Laravel\Knight\Routing\Action;
use HughCube\Laravel\Knight\Support\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ScriptsAction
{
    use Action;
    use LoadedOPcacheExtension;

    /**
     * @return Response
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function action(): Response
    {
        $this->loadedOPcacheExtension();

        $scripts = array_values(array_unique(array_filter(
            array_merge($this->getScripts(), $this->getHistoryScripts())
        )));

        $this->getCache()->set($this->getCacheKey(), $scripts, Carbon::now()->addYears());

        return $this->asJson([
            'count' => count($scripts),
            'scripts' => $scripts
        ]);
    }

    protected function getScripts(): array|string
    {
        if (!function_exists('opcache_get_status')) {
            return [];
        }

        $status = opcache_get_status();

        $scripts = [];
        foreach (($status['scripts'] ?? []) as $script) {
            $scripts[] = ltrim(Str::afterLast($script['full_path'], base_path()), '/');
        }
        return $scripts;
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getHistoryScripts(): array
    {
        $histories = $this->getCache()->get($this->getCacheKey());
        $scripts = is_array($histories) ? $histories : [];

        foreach ($scripts as $index => $file) {
            if (!is_file(base_path($file))) {
                unset($scripts[$index]);
            }
        }

        return array_values($scripts);
    }

    protected function getCacheKey(): string
    {
        $string = serialize([__METHOD__]);
        return strtr(sprintf('%s%s', md5($string), crc32($string)), 0, 30);
    }

    /**
     * @return Repository
     */
    protected function getCache(): Repository
    {
        return Cache::store();
    }
}
