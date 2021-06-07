<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/6
 * Time: 8:29 下午
 */

namespace HughCube\Laravel\Knight\Database\Eloquent;

use Illuminate\Support\Collection;

/**
 * Class Builder
 * @package HughCube\Laravel\Knight\Database\Eloquent
 * @method Model getModel()
 */
class Builder extends \Illuminate\Database\Eloquent\Builder
{
    use \HughCube\Laravel\Knight\Database\Eloquent\Traits\Builder;

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $number = parent::delete();
        if (false !== $number && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $number;
    }

    /**
     * @inheritdoc
     */
    public function update(array $values)
    {
        $number = parent::update($values);
        if (false !== $number && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $number;
    }

    /**
     * @inheritdoc
     */
    public function insert(array $values)
    {
        $number = parent::insert($values);
        if (false !== $number && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $number;
    }

    /**
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function refreshRowCache()
    {
        $cacheKeys = Collection::make($this->getModel()->onChangeRefreshCacheKeys())->mapWithKeys(function ($id, $key) {
            return [$key => $this->makeColumnsCacheKey($id)];
        });
        return $this->getModel()->getCache()->deleteMultiple($cacheKeys->values()->toArray());
    }
}
