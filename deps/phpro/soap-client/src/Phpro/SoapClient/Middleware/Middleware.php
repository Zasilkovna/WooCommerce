<?php

namespace Packetery\Phpro\SoapClient\Middleware;

use Packetery\Http\Client\Exception;
use Packetery\Http\Promise\Promise;
use Packetery\Phpro\SoapClient\Exception\MiddlewareException;
use Packetery\Psr\Http\Message\RequestInterface;
use Packetery\Psr\Http\Message\ResponseInterface;
/**
 * Class Middleware
 *
 * @package Phpro\SoapClient\Middleware
 * @internal
 */
class Middleware implements MiddlewareInterface
{
    public function getName() : string
    {
        return 'empty_middleware';
    }
    public function handleRequest(RequestInterface $request, callable $next, callable $first) : Promise
    {
        return $this->beforeRequest($next, $request)->then((function (ResponseInterface $response) {
            return $this->afterResponse($response);
        })->bindTo($this), (function (Exception $exception) {
            $this->onError($exception);
        })->bindTo($this));
    }
    public function beforeRequest(callable $next, RequestInterface $request) : Promise
    {
        return $next($request);
    }
    public function afterResponse(ResponseInterface $response) : ResponseInterface
    {
        return $response;
    }
    public function onError(Exception $exception)
    {
        throw MiddlewareException::fromHttPlugException($exception);
    }
}
