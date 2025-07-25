<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/17
 * Time: 17:33.
 */

namespace HughCube\Laravel\Knight\Routing;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin Action
 */
trait ListQuery
{
    use PaginateQuery;

    protected function rules(): array
    {
        return [];
    }

    /**
     * @return int|null
     */
    protected function getPage(): ?int
    {
        return null;
    }

    /**
     * @param Builder|mixed $query
     *
     * @return null|int
     */
    protected function queryCount($query): ?int
    {
        return null;
    }

    /**
     * @return Builder|null
     */
    abstract protected function makeQuery(): ?Builder;
}
