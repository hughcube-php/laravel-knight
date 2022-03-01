<?php

namespace HughCube\Laravel\Knight\Exceptions;

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
     * @param Request   $request
     * @param Throwable $e
     *
     * @throws Throwable
     *
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        $e = method_exists($this, 'mapException') ? $this->mapException($e) : $e;
        $e = $this->prepareException($e);

        if ($e instanceof HttpException) {
            $response = new PsrResponse($e->getStatusCode());
            $results = ['code' => $response->getStatusCode(), 'message' => $response->getReasonPhrase()];
        } elseif ($e instanceof AuthenticationException) {
            $results = ['code' => 401, 'message' => '请先登录!'];
        } elseif ($e instanceof ValidationException) {
            $results = ['code' => $e->status, 'message' => '非法请求!', 'errors' => $e->errors()];
        } elseif ($e instanceof ExceptionWithData) {
            $results = ['code' => $e->getCode(), 'message' => $e->getMessage(), 'data' => $e->getData()];
        } elseif ($e instanceof UserException) {
            $results = ['code' => $e->getCode(), 'message' => $e->getMessage()];
        } elseif ($e instanceof Exception) {
            $results = ['code' => 500, 'message' => $e->getMessage()];
        } else {
            $results = ['code' => 500, 'message' => '服务器繁忙, 请稍后再试!'];
        }

        $results['data'] = empty($results['data']) ? new stdClass() : $results['data'];
        if (true == config('app.debug')) {
            $results['debug'] = $this->convertExceptionToArray($e);
        }

        return response()->json($results);
    }

    /**
     * Converts an exception into an array.
     *
     * @param Throwable $e
     *
     * @return array the array representation of the exception.
     */
    protected function convertExceptionToArray(Throwable $e): array
    {
        $array = [
            'name'        => get_class($e),
            'message'     => $e->getMessage(),
            'code'        => $e->getCode(),
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
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
}
