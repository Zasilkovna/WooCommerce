<?php

namespace Packetery\Phpro\SoapClient\Middleware;

use Packetery\Http\Client\Common\Plugin;
use Packetery\Http\Client\Exception;
use Packetery\Http\Promise\Promise;
use Packetery\Psr\Http\Message\RequestInterface;
use Packetery\Psr\Http\Message\ResponseInterface;
/**
 * Class MiddlewareInterface
 *
 * @package Phpro\SoapClient\Middleware
 * @internal
 */
interface MiddlewareInterface extends Plugin
{
    public function beforeRequest(callable $handler, RequestInterface $request) : Promise;
    public function afterResponse(ResponseInterface $response) : ResponseInterface;
    public function onError(Exception $exception);
    public function getName() : string;
}
