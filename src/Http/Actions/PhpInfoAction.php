<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 21:36.
 */

namespace HughCube\Laravel\Knight\Http\Actions;

use HughCube\Laravel\Knight\Routing\Controller;

class PhpInfoAction extends Controller
{
    protected function action(): string
    {
        ob_start();
        $results = phpinfo();
        $output = ob_get_contents();
        ob_end_clean();

        /** @phpstan-ignore-next-line */
        if (false === $results) {
            return 'The phpinfo call failed!';
        }

        return nl2br($output);
    }
}
