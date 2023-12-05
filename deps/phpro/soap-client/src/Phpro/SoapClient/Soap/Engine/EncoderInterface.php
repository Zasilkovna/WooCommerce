<?php

namespace Packetery\Phpro\SoapClient\Soap\Engine;

use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
/** @internal */
interface EncoderInterface
{
    public function encode(string $method, array $arguments) : SoapRequest;
}
