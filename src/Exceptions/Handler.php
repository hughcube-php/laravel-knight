<?php

namespace HughCube\Laravel\Knight\Exceptions;

use HughCube\Laravel\Knight\Exceptions\Contracts\DataExceptionInterface;
use HughCube\Laravel\Knight\Exceptions\Contracts\ResponseExceptionInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * @inheritdoc
     */
    protected $dontReport = [
        HttpException::class,
        UserException::class,
        DataExceptionInterface::class,
        ResponseExceptionInterface::class,
    ];

    /**
     * @inheritdoc
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    protected function context(): array
    {
        $context = parent::context();

        try {
            $context['uri'] = request()->getUri();
            $context['headers'] = request()->headers->all();
            $context['body'] = request()->getContent();
        } catch (Throwable $exception) {
        }

        return $context;
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        if (method_exists($this, 'reportable')) {
            $this->reportable(function (Throwable $e) {
            });
        }
    }

    /**
     * @param  Throwable  $e
     *
     * @return null|array
     */
    protected function convertExceptionToResponseData(Throwable $e): ?array
    {
        return null;
    }

    /**
     * @param  Request  $request
     * @param  Throwable  $e
     *
     * @return Response
     * @throws Throwable
     *
     */
    public function render($request, Throwable $e): Response
    {
        $e = method_exists($this, 'mapException') ? $this->mapException($e) : $e;
        $e = $this->prepareException($e);

        if (!empty($data = $this->convertExceptionToResponseData($e))) {
            $results = $data;
        } elseif ($e instanceof ResponseExceptionInterface) {
            $results = $e->getResponse();
        } elseif ($e instanceof DataExceptionInterface) {
            $results = ['code' => $e->getCode(), 'message' => $e->getMessage(), 'data' => $e->getData()];
        } elseif ($e instanceof AuthenticationException) {
            $results = ['code' => 401, 'message' => '请先登录!'];
        } elseif ($e instanceof ValidationException) {
            $results = ['code' => $e->status, 'message' => '非法请求!', 'errors' => $e->errors()];
        } elseif ($e instanceof UserException) {
            $results = ['code' => $e->getCode(), 'message' => $e->getMessage()];
        } elseif ($e instanceof Exception) {
            $results = ['code' => 500, 'message' => $e->getMessage()];
        } elseif ($e instanceof HttpException) {
            $results = ['code' => $e->getStatusCode(), 'message' => Response::$statusTexts[$e->getStatusCode()] ?? ''];
        } else {
            $results = ['code' => 500, 'message' => '服务器繁忙, 请稍后再试!'];
        }

        $results['data'] = empty($results['data']) ? new stdClass() : $results['data'];

        if (config('app.debug')) {
            $results['debug'] = $this->convertExceptionToArray($e);
        }

        return $this->convertResultsToResponse($results);
    }

    /**
     * Converts an exception into an array.
     *
     * @param  Throwable  $e
     *
     * @return array the array representation of the exception.
     */
    protected function convertExceptionToArray(Throwable $e): array
    {
        $array = [
            'name' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'stack-trace' => explode("\n", $e->getTraceAsString()),
        ];

        if ($e instanceof ValidationException) {
            $array['errors'] = $e->errors();
        }

        if (($prev = $e->getPrevious()) !== null) {
            $array['previous'] = $this->convertExceptionToArray($prev);
        }

        return $array;
    }

    /**
     * @param  array|Response|string  $results
     * @return Response
     */
    protected function convertResultsToResponse($results): Response
    {
        if ($results instanceof Response) {
            return $results;
        }

        if (is_string($results)) {
            return new Response($results);
        }

        return new JsonResponse($results);
    }
}
