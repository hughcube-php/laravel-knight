<?php

namespace HughCube\Laravel\Knight\Exceptions;

/**
 * Service 层业务规则异常
 *
 * 与 UserException 无继承关系, 不在 $dontReport 中, 所有上下文都会触发告警.
 * HTTP 请求中由中间件捕获并转为 UserException, 实现友好消息渲染.
 */
class BusinessRuleException extends \RuntimeException
{
}
