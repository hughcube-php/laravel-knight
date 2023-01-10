<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/3
 * Time: 15:03.
 */

namespace HughCube\Laravel\Knight\Mixin\Database\Query;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;

/**
 * @mixin Builder
 */
class BuilderMixin
{
    public function whereJsonOverlaps(): Closure
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

    public function orWhereJsonOverlaps(): Closure
    {
        return function ($column, $value) {
            /** @phpstan-ignore-next-line  */
            return $this->whereJsonOverlaps($column, $value, 'or');
        };
    }
}
