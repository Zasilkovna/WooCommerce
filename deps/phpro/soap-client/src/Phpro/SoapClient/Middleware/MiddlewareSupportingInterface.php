<?php

namespace Packetery\Phpro\SoapClient\Middleware;

/**
 * Class MiddlewareSupportingInterface
 *
 * @package Phpro\SoapClient\Middleware
 * @internal
 */
interface MiddlewareSupportingInterface
{
    /**
     * @param MiddlewareInterface $middleware
     *
     * @return void
     */
    public function addMiddleware(MiddlewareInterface $middleware);
}
