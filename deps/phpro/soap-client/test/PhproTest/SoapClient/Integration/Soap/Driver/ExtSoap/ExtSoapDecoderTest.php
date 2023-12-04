<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Integration\Soap\Driver\ExtSoap;

use Packetery\Phpro\SoapClient\Soap\ClassMap\ClassMap;
use Packetery\Phpro\SoapClient\Soap\ClassMap\ClassMapCollection;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\AbusedClient;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapDecoder;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapMetadata;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Generator\DummyMethodArgumentsGenerator;
use Packetery\Phpro\SoapClient\Soap\Engine\DecoderInterface;
use Packetery\PhproTest\SoapClient\Integration\Soap\Engine\AbstractDecoderTest;
use Packetery\PhproTest\SoapClient\Integration\Soap\Type\ValidateResponse;
/** @internal */
class ExtSoapDecoderTest extends AbstractDecoderTest
{
    /**
     * @var ExtSoapDecoder
     */
    private $decoder;
    protected function getDecoder() : DecoderInterface
    {
        return $this->decoder;
    }
    protected function configureForWsdl(string $wsdl)
    {
        $this->decoder = new ExtSoapDecoder($client = AbusedClient::createFromOptions(ExtSoapOptions::defaults($wsdl, [])->disableWsdlCache()->withClassMap(new ClassMapCollection([new ClassMap('MappedValidateResponse', ValidateResponse::class)]))), new DummyMethodArgumentsGenerator(new ExtSoapMetadata($client)));
    }
}
