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
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\HttpFoundation\Response;

class StatesAction extends Controller
{
    use LoadedOPcacheExtension;

    protected function rules(): array
    {
        return [
            'as_json' => ['remove_if_empty', 'default:0', 'boolean'],
        ];
    }

    /**
     * @throws Exception
     * @throws BindingResolutionException
     *
     * @return Response
     */
    protected function action(): Response
    {
        $this->loadedOPcacheExtension();

        if ($this->isAsJson()) {
            return $this->asResponse(opcache_get_status());
        }

        return response($this->renderView(dirname(__DIR__).'/Views/opcache.php'));
    }

    protected function renderView($file)
    {
        $obLevel = ob_get_level();
        ob_start();
        include $file;

        return ob_get_clean();
    }

    protected function isAsJson(): bool
    {
        return 1 == $this->p()->get('as_json');
    }
}
