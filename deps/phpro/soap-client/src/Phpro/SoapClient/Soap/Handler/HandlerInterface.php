<?php

namespace Packetery\Phpro\SoapClient\Soap\Handler;

use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapResponse;
/**
 * Class HandlerInterface
 *
 * @package Phpro\SoapClient\Soap\Handler
 * @internal
 */
interface HandlerInterface extends LastRequestInfoCollectorInterface
{
    /**
     * @param SoapRequest $request
     *
     * @return SoapResponse
     */
    public function request(SoapRequest $request) : SoapResponse;
}
