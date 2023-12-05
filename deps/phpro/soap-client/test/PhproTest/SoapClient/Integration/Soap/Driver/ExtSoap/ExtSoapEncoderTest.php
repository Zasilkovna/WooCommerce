<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Integration\Soap\Driver\ExtSoap;

use Packetery\Phpro\SoapClient\Soap\ClassMap\ClassMap;
use Packetery\Phpro\SoapClient\Soap\ClassMap\ClassMapCollection;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\AbusedClient;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEncoder;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Packetery\Phpro\SoapClient\Soap\Engine\EncoderInterface;
use Packetery\PhproTest\SoapClient\Integration\Soap\Engine\AbstractEncoderTest;
use Packetery\PhproTest\SoapClient\Integration\Soap\Type\ValidateRequest;
/** @internal */
class ExtSoapEncoderTest extends AbstractEncoderTest
{
    /**
     * @var ExtSoapEncoder
     */
    private $encoder;
    protected function getEncoder() : EncoderInterface
    {
        return $this->encoder;
    }
    protected function configureForWsdl(string $wsdl)
    {
        $this->encoder = new ExtSoapEncoder($client = AbusedClient::createFromOptions(ExtSoapOptions::defaults($wsdl)->disableWsdlCache()->withClassMap(new ClassMapCollection([new ClassMap('MappedValidateRequest', ValidateRequest::class)]))));
    }
}
