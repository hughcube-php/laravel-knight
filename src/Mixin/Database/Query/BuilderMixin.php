<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/3
 * Time: 15:03.
 */

namespace HughCube\Laravel\Knight\Mixin\Database\Query;

use Closure;
use HughCube\Laravel\Knight\Traits\SimpleMacroableBridge;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;

/**
 * @mixin Builder
 */
class BuilderMixin
{
    use SimpleMacroableBridge;

    public static function getMacros(): array
    {
        return [
            'whereJsonOverlaps', 'orWhereJsonOverlaps',
        ];
    }

    public static function whereJsonOverlaps(): Closure
    {
        return function ($column, $value, $boolean = 'and', $not = false) {
            $type = 'JsonOverlaps';

            /** @phpstan-ignore-next-line */
            $this->wheres[] = compact('type', 'column', 'value', 'boolean', 'not');

            if (!$value instanceof Expression) {
                /** @phpstan-ignore-next-line */
                $this->addBinding($this->grammar->prepareBindingForJsonContains($value));
            }

            return $this;
        };
    }

    public static function orWhereJsonOverlaps(): Closure
    {
        return function ($column, $value) {
            /** @phpstan-ignore-next-line */
            return $this->whereJsonOverlaps($column, $value, 'or');
        };
    }
}
