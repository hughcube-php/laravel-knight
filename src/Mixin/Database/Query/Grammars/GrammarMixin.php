<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/3
 * Time: 15:01.
 */

namespace HughCube\Laravel\Knight\Mixin\Database\Query\Grammars;

use Closure;
use HughCube\Laravel\Knight\Traits\SimpleMacroableBridge;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

/**
 * @mixin Grammar
 */
class GrammarMixin
{
    use SimpleMacroableBridge;

    public static function getMacros(): array
    {
        return [
            'orWhereJsonOverlaps', 'whereJsonOverlaps', 'compileJsonOverlaps'
        ];
    }

    public static function orWhereJsonOverlaps(): Closure
    {
        return function ($column, $org, $tags) {
            /** @phpstan-ignore-next-line */
            return $this->whereJsonOverlaps($column, $org, $tags, 'or');
        };
    }

    public static function whereJsonOverlaps(): Closure
    {
        return function (Builder $query, $where) {
            $not = $where['not'] ? 'not ' : '';

            /** @phpstan-ignore-next-line */
            return $not.$this->compileJsonOverlaps($where['column'], $this->parameter($where['value']));
        };
    }

    public static function compileJsonOverlaps(): Closure
    {
        return function ($column, $value) {
            /** @phpstan-ignore-next-line */
            [$field, $path] = $this->wrapJsonFieldAndPath($column);

            return 'json_overlaps('.$field.', '.$value.$path.')';
        };
    }
}
