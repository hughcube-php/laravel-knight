<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2023/3/21
 * Time: 13:25
 */

namespace HughCube\Laravel\Knight\Console;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Command extends \Illuminate\Console\Command
{
    protected function getOrAskOption($name, $question, $default = null): mixed
    {
        $value = $this->option($name);
        if (!empty($value)) {
            return $value;
        }
        return $this->ask($question, $default);
    }

    protected function getOrAskOptionIds($name, $question, $default = null): Collection
    {
        $ids = $this->getOrAskOption($name, $question, $default);

        return Collection::wrap(explode(',', $ids))->unique()->filter()->values();
    }

    protected function getOrAskBoolOption($name, $question, $default = false): bool
    {
        $value = $this->option($name);
        if (null !== $value && '' !== $value) {
            /** @phpstan-ignore-next-line */
            return Str::isTrue($value);
        }
        return $this->confirm($question, $default);
    }
}
