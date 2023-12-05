<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Integration\Soap\Engine;

use DOMElement;
use DOMNodeList;
use Packetery\Phpro\SoapClient\Xml\SoapXml;
use Packetery\PHPUnit\Framework\TestCase;
/** @internal */
abstract class AbstractIntegrationTest extends TestCase
{
    protected abstract function configureForWsdl(string $wsdl);
    protected function runXpathOnBody(SoapXml $xml, string $xpath) : DOMNodeList
    {
        $results = $xml->xpath($xpath, $xml->getBody());
        $this->assertGreaterThan(0, $results->length);
        return $results;
    }
    protected function runSingleElementXpathOnBody(SoapXml $xml, string $xpath) : DOMElement
    {
        $results = $xml->xpath($xpath, $xml->getBody());
        $this->assertEquals(1, $results->length);
        return $results->item(0);
    }
}
