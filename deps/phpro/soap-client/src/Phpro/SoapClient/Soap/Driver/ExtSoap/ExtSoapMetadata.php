<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap;

use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\MethodsParser;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\TypesParser;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\XsdTypesParser;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\MethodCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\TypeCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\XsdTypeCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\MetadataInterface;
/** @internal */
class ExtSoapMetadata implements MetadataInterface
{
    /**
     * @var AbusedClient
     */
    private $abusedClient;
    /**
     * @var XsdTypeCollection|null
     */
    private $xsdTypes;
    public function __construct(AbusedClient $abusedClient)
    {
        $this->abusedClient = $abusedClient;
    }
    public function getMethods() : MethodCollection
    {
        return (new MethodsParser($this->getXsdTypes()))->parse($this->abusedClient);
    }
    public function getTypes() : TypeCollection
    {
        return (new TypesParser($this->getXsdTypes()))->parse($this->abusedClient);
    }
    private function getXsdTypes() : XsdTypeCollection
    {
        if (null === $this->xsdTypes) {
            $this->xsdTypes = XsdTypesParser::default()->parse($this->abusedClient);
        }
        return $this->xsdTypes;
    }
}
