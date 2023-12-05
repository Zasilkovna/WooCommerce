<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap;

use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Handler\ExtSoapClientHandle;
use Packetery\Phpro\SoapClient\Soap\Engine\Engine;
use Packetery\Phpro\SoapClient\Soap\Handler\HandlerInterface;
/** @internal */
class ExtSoapEngineFactory
{
    public static function fromOptions(ExtSoapOptions $options) : Engine
    {
        $driver = ExtSoapDriver::createFromOptions($options);
        $handler = new ExtSoapClientHandle($driver->getClient());
        return new Engine($driver, $handler);
    }
    public static function fromOptionsWithHandler(ExtSoapOptions $options, HandlerInterface $handler) : Engine
    {
        $driver = ExtSoapDriver::createFromOptions($options);
        return new Engine($driver, $handler);
    }
}
