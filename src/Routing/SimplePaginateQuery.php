<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/17
 * Time: 17:33.
 */

namespace HughCube\Laravel\Knight\Routing;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * @mixin Action
 */
trait SimplePaginateQuery
{
    protected function rules(): array
    {
        return $this->appendPaginateRules([]);
    }

    protected function appendPaginateRules($rules = []): array
    {
        $rules['page'] = ['remove_if_empty_string', 'remove_if_null', 'integer', 'min:1', 'remove_if_empty'];
        $rules['page_size'] = ['remove_if_empty_string', 'remove_if_null', 'integer', 'min:1', 'remove_if_empty'];

        return $rules;
    }

    /**
     * @throws Exception
     */
    protected function action(): JsonResponse
    {
        $query = $this->makeQuery();

        $page = $this->getPage();
        $pageSize = $this->getPageSize();
        $count = $this->queryCount($query);

        $offset = $this->getOffset($page, $pageSize);
        $collection = $this->queryCollection($query, $offset, $pageSize);

        $results = ['list' => $this->formatCollection($collection)];
        null !== $count and $results['count'] = $count;
        null !== $page and $results['page'] = $page;
        null !== $pageSize and $results['page_size'] = $pageSize;

        return $this->asJson($this->formatResults($results));
    }

    /**
     * @return int|null
     */
    protected function getPage(): ?int
    {
        return $this->p()->getInt('page') ?: 1;
    }

    /**
     * @return int|null
     */
    protected function getPageSize(): ?int
    {
        return $this->p()->getInt('page_size') ?: 10;
    }

    /**
     * @param  int|null  $page
     * @param  int|null  $pageSize
     *
     * @return int|null
     */
    protected function getOffset(?int $page, ?int $pageSize): ?int
    {
        if (is_int($page) && is_int($pageSize)) {
            return ($page - 1) * $pageSize;
        }

        return null;
    }

    /**
     * @return Builder|null
     */
    abstract protected function makeQuery(): ?Builder;

    /**
     * @param  Builder|mixed  $query
     *
     * @return null|int
     */
    protected function queryCount($query): ?int
    {
        if ($query instanceof Builder) {
            return $query->count();
        }
        return 0;
    }

    /**
     * @param  Builder|mixed  $query
     * @param  int|null  $offset
     * @param  int|null  $limit
     *
     * @return Collection
     */
    protected function queryCollection($query, ?int $offset, ?int $limit): Collection
    {
        if ($query instanceof Builder && is_int($limit)) {
            $query->limit($limit);
        }

        if ($query instanceof Builder && is_int($offset)) {
            $query->offset($offset);
        }

        if ($query instanceof Builder) {
            return $query->get();
        }

        return Collection::make();
    }

    /**
     * @param  Collection  $rows
     *
     * @return array
     */
    protected function formatCollection(Collection $rows): array
    {
        return $rows->toArray();
    }

    /**
     * @param  mixed  $results
     *
     * @return mixed
     */
    protected function formatResults($results)
    {
        return $results;
    }
}
