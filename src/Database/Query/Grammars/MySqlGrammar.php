<?php

namespace HughCube\Laravel\Knight\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\MySqlGrammar as BaseMySqlGrammar;

class MySqlGrammar extends BaseMySqlGrammar
{
    /**
     * Compile a "JSON overlaps" statement into SQL.
     *
     * @param string $column
     * @param string $value
     *
     * @return string
     */
    protected function compileJsonOverlaps($column, $value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        if ($path !== '') {
            $field = 'json_extract(' . $field . $path . ')';
        }

        return 'json_overlaps(' . $field . ', ' . $value . ')';
    }
}
