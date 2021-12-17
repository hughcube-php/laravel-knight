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
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\View\View;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\Response;

class StatesAction
{
    use Action;
    use LoadedOPcacheExtension;

    #[ArrayShape([])]
    protected function rules(): array
    {
        return [
            'as_json' => ['remove_if_empty', 'default:0', 'boolean'],
        ];
    }

    /**
     * @return Response
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function action(): Response
    {
        $this->loadedOPcacheExtension();

        if ($this->isAsJson()) {
            return $this->asJson(opcache_get_status());
        }

        return response(require dirname(__DIR__).'/Views/opcache.php');
    }

    protected function isAsJson(): bool
    {
        /** @phpstan-ignore-next-line */
        return 1 == $this->get('as_json');
    }
}
