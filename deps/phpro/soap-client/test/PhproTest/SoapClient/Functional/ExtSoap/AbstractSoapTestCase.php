<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Functional\ExtSoap;

use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapDriver;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Handler\ExtSoapServerHandle;
use Packetery\PHPUnit\Framework\TestCase;
/** @internal */
abstract class AbstractSoapTestCase extends TestCase
{
    protected function configureSoapDriver(string $wsdl, array $options) : ExtSoapDriver
    {
        return ExtSoapDriver::createFromOptions(ExtSoapOptions::defaults($wsdl, $options)->disableWsdlCache());
    }
    protected function configureServer(string $wsdl, array $options, $object) : ExtSoapServerHandle
    {
        $options = ExtSoapOptions::defaults($wsdl, $options)->disableWsdlCache();
        $server = new \SoapServer($options->getWsdl(), $options->getOptions());
        $server->setObject($object);
        return new ExtSoapServerHandle($server);
    }
}
