<?php

namespace Packetery\Phpro\SoapClient\Soap\Engine;

use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapResponse;
/** @internal */
interface DecoderInterface
{
    /**
     * @return mixed
     */
    public function decode(string $method, SoapResponse $response);
}
