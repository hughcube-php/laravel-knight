<?php

namespace Illuminate\Database\Schema\Grammars;

use HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

/**
 * IDE helper stub for Postgres schema grammar mixins.
 *
 * @see PostgresGrammarMixin
 */
class PostgresGrammar
{
    /**
     * @see PostgresGrammarMixin::compileKnightGinIndex()
     */
    public function compileKnightGinIndex(Blueprint $blueprint, Fluent $command): string
    {
        return '';
    }

    /**
     * @see PostgresGrammarMixin::compileKnightUniqueWhere()
     */
    public function compileKnightUniqueWhere(Blueprint $blueprint, Fluent $command): string
    {
        return '';
    }

    /**
     * @see PostgresGrammarMixin::compileKnightIndexWhere()
     */
    public function compileKnightIndexWhere(Blueprint $blueprint, Fluent $command): string
    {
        return '';
    }
}
