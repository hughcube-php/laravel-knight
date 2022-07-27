<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/27
 * Time: 21:44.
 */

namespace HughCube\Laravel\Knight\Http\Middleware;

use Closure;
use HughCube\Laravel\Knight\Contracts\Support\GetUserLoginAccessSecret;
use HughCube\Laravel\Knight\Exceptions\ValidateSignatureException;
use HughCube\Laravel\Knight\Ide\Http\KIdeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class RequestSignatureValidate
{
    protected $optional = null;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @throws ValidateSignatureException
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->validate($request) || $this->isOptional($request)) {
            return $next($request);
        }

        throw new ValidateSignatureException();
    }

    protected function validate(Request $request): bool
    {
        /** @var Request|KIdeRequest $request */

        /** 签名不能为空 */
        $signature = $request->getClientSignature();
        if (null == $signature) {
            return false;
        }

        $nonce = $request->getClientNonce();
        if (empty($nonce) || 10 > strlen($nonce)) {
            return false;
        }

        return $this->makeSignature($request) === $signature;
    }

    protected function makeSignature(Request $request): ?string
    {
        /** @var Request|KIdeRequest $request */

        /** @var null|GetUserLoginAccessSecret $user */
        $user = $request->getUser();

        $string = sprintf(
            "%s\n%s\n%s\n%s\n%s\n%s",

            /** HTTP METHOD */
            strtoupper($request->getMethod()),

            /** HTTP DATE */
            $request->getDate() ?: '',

            /** HTTP CONTENT TYPE */
            $request->headers->get('CONTENT_TYPE', ''),

            /** HTTP CLIENT HEADERS */
            Collection::make($request->getClientHeaders()->all())
                ->forget([
                    strtolower(sprintf('%sSignature', $request->getClientHeaderPrefix())),
                ])
                ->sortKeys()
                ->map(function ($value, $key) {
                    return sprintf('%s=%s', $key, implode(',', Arr::wrap($value)));
                })
                ->implode('&'),

            /** HTTP CONTENT */
            $request->getContent(),

            /** USER ACCESS SECRET */
            $user instanceof GetUserLoginAccessSecret ? $user->getUserLoginAccessSecret() : ''
        );

        return md5($string);
    }

    protected function isOptional(Request $request): bool
    {
        return $request->is($this->getOptional()) || $request->fullUrlIs($this->getOptional());
    }

    protected function getOptional()
    {
        return ($this->optional ?: config('signature.optional')) ?: [];
    }
}
