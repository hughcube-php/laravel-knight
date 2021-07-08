<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/3/13
 * Time: 11:46 下午.
 */

namespace HughCube\Laravel\Knight\Routing;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait AliFcInvokeAction
{
    use Action;

    public function action()
    {
        $this->log(sprintf("payload: %s", base64_encode($this->getRequest()->getContent())));

        $methods = [];
        foreach (get_class_methods($this) as $method) {
            if (Str::endsWith(strtolower($method), 'action') && Str::startsWith(strtolower($method), 'do')) {
                $methods[] = $method;
            }
        }

        foreach ($methods as $method) {
            if (false === $this->{$method}()) {
                break;
            }
        }

        return "done";
    }

    /**
     * @return array|null
     */
    protected function getPayload()
    {
        return $this->getOrSet(__METHOD__, function () {
            $payload = null;
            try {
                $content = $this->getRequest()->getContent();
                $content = json_decode($content, true);

                $payload = Arr::get($content, 'payload');
                $payload = json_decode($payload, true);
            } catch (\Throwable $exception) {
            }

            return $payload;
        });
    }

    /**
     * @return string|null
     */
    protected function getPayloadAction()
    {
        $action = Arr::get($this->getPayload(), "action");
        return is_string($action) ? strtolower($action) : $action;
    }

    /**
     * @return array
     */
    protected function getPayloadData()
    {
        return Arr::get($this->getPayload(), "data", []);
    }

    /**
     * @return array
     */
    protected function getPayloadUUID()
    {
        return $this->getOrSet(__METHOD__, function () {
            $uuid = Arr::get($this->getPayload(), "uuid");
            return empty($uuid) ? Str::uuid() : $uuid;
        });
    }

    /**
     * log
     *
     * @param string $message
     * @param string $level
     */
    protected function log($message, $level = 'info')
    {
        Log::log(
            $level,
            sprintf("action: %s, uuid:%s, %s", $this->getPayloadAction(), $this->getPayloadUUID(), $message)
        );
    }
}
