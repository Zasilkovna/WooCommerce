<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Integration\Soap\Driver\ExtSoap;

use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\AbusedClient;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapDriver;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\MetadataProviderInterface;
use Packetery\PhproTest\SoapClient\Integration\Soap\Engine\AbstractMetadataProviderTest;
/** @internal */
class ExtSoapMetadataProviderTest extends AbstractMetadataProviderTest
{
    /**
     * @var MetadataProviderInterface
     */
    private $metadataProvider;
    /**
     * @var AbusedClient
     */
    protected $client;
    protected function getMetadataProvider() : MetadataProviderInterface
    {
        return $this->metadataProvider;
    }
    protected function configureForWsdl(string $wsdl)
    {
        $this->metadataProvider = ExtSoapDriver::createFromClient($this->client = AbusedClient::createFromOptions(ExtSoapOptions::defaults($wsdl)->disableWsdlCache()));
    }
}
