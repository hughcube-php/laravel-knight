<?php

namespace HughCube\Laravel\Knight\Listeners;

use HughCube\Laravel\Knight\Events\WalChangesDetected;

/**
 * Default synchronous listener: query models from DB then call onKnightModelChanged().
 * App can replace this with a queued version by registering its own listener.
 */
class WalChangesListener
{
    public function handle(WalChangesDetected $event): void
    {
        $handler = $event->handler;
        $keyName = method_exists($handler, 'getKeyName') ? $handler->getKeyName() : 'id';

        /** @phpstan-ignore-next-line */
        $models = $handler::query()->whereIn($keyName, $event->ids)->get()->keyBy($keyName);

        foreach ($event->ids as $id) {
            $model = $models->get($id);
            if (null === $model) {
                $model = clone $handler;
                $model->{$keyName} = $id;
            }

            if (method_exists($model, 'onKnightWalChanged')) {
                $model->onKnightWalChanged();
            }

            if (method_exists($model, 'onKnightModelChanged')) {
                $model->onKnightModelChanged();
            }
        }
    }
}
