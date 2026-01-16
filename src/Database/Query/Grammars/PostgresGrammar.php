<?php

namespace HughCube\Laravel\Knight\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\PostgresGrammar as BasePostgresGrammar;

class PostgresGrammar extends BasePostgresGrammar
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
        $column = str_replace('->>', '->', $this->wrap($column));
        $lhs = '('.$column.')::jsonb';
        $rhs = $value.'::jsonb';
        $alias = 'json_overlaps_values';

        return 'exists (select 1 from (select '.$rhs.' as v, '.$lhs.' as lhs) as '.$alias.' where case'
            ." when jsonb_typeof({$alias}.lhs) = 'object' and jsonb_typeof({$alias}.v) = 'object' then "
            .'exists (select 1 from jsonb_each('.$alias.'.lhs) l join jsonb_each('.$alias.'.v) r on l.key = r.key and l.value = r.value)'
            ." when jsonb_typeof({$alias}.lhs) = 'array' and jsonb_typeof({$alias}.v) = 'array' then "
            .'exists (select 1 from jsonb_array_elements('.$alias.'.lhs) l join jsonb_array_elements('.$alias.'.v) r on l = r)'
            ." when jsonb_typeof({$alias}.lhs) = 'array' then "
            .'exists (select 1 from jsonb_array_elements('.$alias.'.lhs) l where l = '.$alias.'.v)'
            ." when jsonb_typeof({$alias}.v) = 'array' then "
            .'exists (select 1 from jsonb_array_elements('.$alias.'.v) r where r = '.$alias.'.lhs)'
            .' else '.$alias.'.lhs = '.$alias.'.v end)';
    }
}
