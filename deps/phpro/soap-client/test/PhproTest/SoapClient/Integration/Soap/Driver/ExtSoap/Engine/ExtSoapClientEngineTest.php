<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Integration\Soap\Driver\ExtSoap\Engine;

use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\AbusedClient;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapDriver;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Handler\ExtSoapClientHandle;
use Packetery\Phpro\SoapClient\Soap\Engine\Engine;
use Packetery\Phpro\SoapClient\Soap\Engine\EngineInterface;
use Packetery\Phpro\SoapClient\Soap\Handler\HandlerInterface;
use Packetery\PhproTest\SoapClient\Integration\Soap\Engine\AbstractEngineTest;
/** @internal */
class ExtSoapClientEngineTest extends AbstractEngineTest
{
    /**
     * @var EngineInterface
     */
    private $engine;
    /**
     * @var HandlerInterface
     */
    private $handler;
    protected function getEngine() : EngineInterface
    {
        return $this->engine;
    }
    protected function getHandler() : HandlerInterface
    {
        return $this->handler;
    }
    protected function getVcrPrefix() : string
    {
        return 'ext-soap-with-client-handle-';
    }
    protected function skipVcr() : bool
    {
        return \false;
    }
    protected function skipLastHeadersCheck() : bool
    {
        return \true;
    }
    protected function configureForWsdl(string $wsdl)
    {
        $this->engine = new Engine(ExtSoapDriver::createFromClient($client = AbusedClient::createFromOptions(ExtSoapOptions::defaults($wsdl, ['cache_wsdl' => \WSDL_CACHE_NONE, 'soap_version' => \SOAP_1_2]))), $this->handler = new ExtSoapClientHandle($client));
    }
}
