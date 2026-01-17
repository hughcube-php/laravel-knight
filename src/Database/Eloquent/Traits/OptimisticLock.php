<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/4
 * Time: 12:00 下午.
 */

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use HughCube\Laravel\Knight\Exceptions\OptimisticLockException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Trait OptimisticLocking.
 *
 * @mixin EloquentModel
 */
trait OptimisticLock
{
    protected bool $enableOptimisticLock = true;

    protected static function bootOptimisticLock()
    {
        static::creating(function ($model) {
            if (null === $model->{$model->lockDataVersionColumn()}) {
                $model->{$model->lockDataVersionColumn()} = $model->defaultModelDataVersion();
            }

            return $model;
        });
    }

    public static function lockDataVersionColumn(): string
    {
        return defined(static::class . '::DATA_VERSION') ? static::DATA_VERSION : 'data_version';
    }

    public static function defaultModelDataVersion(): int
    {
        return 1;
    }

    public function enableOptimisticLock()
    {
        $this->enableOptimisticLock = true;

        return $this;
    }

    public function disableOptimisticLock()
    {
        $this->enableOptimisticLock = false;

        return $this;
    }

    public function optimisticLockEnabled(): bool
    {
        return $this->enableOptimisticLock === true;
    }

    protected function performUpdate(Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = method_exists($this, 'getDirtyForUpdate')
            ? $this->getDirtyForUpdate()
            : $this->getDirty();

        if (count($dirty) > 0) {
            if ($this->optimisticLockEnabled()) {
                $oldVersion = $this->{$this->lockDataVersionColumn()};
                $newVersion = $oldVersion + 1;

                $dirty[$this->lockDataVersionColumn()] = $newVersion;

                $affected = $this->setKeysForSaveQuery($query)
                    ->where($this->lockDataVersionColumn(), $oldVersion)
                    ->update($dirty);

                if ($affected === 0) {
                    throw new OptimisticLockException(
                        'Optimistic lock failed: The record has been modified by another process.'
                    );
                }

                $this->{$this->lockDataVersionColumn()} = $newVersion; // Update model instance
            } else {
                $this->setKeysForSaveQuery($query)->update($dirty);
            }

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }
}
