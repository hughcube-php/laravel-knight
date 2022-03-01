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
        return [
            'page'      => ['remove_if_empty', 'default:1', 'required', 'integer', 'min:1'],
            'page_size' => ['remove_if_empty', 'default:15', 'required', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * @throws Exception
     */
    protected function action(): JsonResponse
    {
        $query = $this->makeQuery();
        $count = $query->count();

        $page = $this->getPage();
        $pageSize = $this->getPageSize();
        $rows = $query->forPage($page, $pageSize)->get();

        $list = $this->buildList($rows);

        return $this->asJson([
            'count'     => $count,
            'page'      => $page,
            'page_size' => $pageSize,
            'list'      => array_values($list),
        ]);
    }

    /**
     * @return int
     */
    protected function getPage(): int
    {
        return $this->p()->getInt('page');
    }

    /**
     * @return int
     */
    protected function getPageSize(): int
    {
        return $this->p()->getInt('page_size');
    }

    abstract protected function makeQuery(): Builder;

    /**
     * @param Collection|array $rows
     *
     * @return array
     */
    protected function buildList($rows): array
    {
        $rows = $rows instanceof Collection ? $rows->toArray() : $rows;

        return array_values($rows);
    }
}
