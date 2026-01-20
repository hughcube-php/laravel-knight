<?php

namespace Illuminate\Database\Query\Grammars;

use HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin;
use Illuminate\Database\Query\Builder;

/**
 * IDE helper stub for Grammar mixins.
 *
 * @see GrammarMixin
 */
class Grammar
{
    /**
     * @see GrammarMixin::whereArrayContains()
     */
    public function whereArrayContains(Builder $query, array $where): string
    {
        return '';
    }

    /**
     * @see GrammarMixin::whereArrayContainedBy()
     */
    public function whereArrayContainedBy(Builder $query, array $where): string
    {
        return '';
    }

    /**
     * @see GrammarMixin::whereArrayOverlaps()
     */
    public function whereArrayOverlaps(Builder $query, array $where): string
    {
        return '';
    }

    /**
     * @see GrammarMixin::compilePgArrayExpression()
     */
    public function compilePgArrayExpression(array $values, ?string $arrayType = null): string
    {
        return '';
    }
}
