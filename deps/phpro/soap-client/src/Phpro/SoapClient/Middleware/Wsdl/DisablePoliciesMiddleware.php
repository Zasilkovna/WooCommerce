<?php

namespace Packetery\Phpro\SoapClient\Middleware\Wsdl;

use Packetery\Phpro\SoapClient\Middleware\Middleware;
use Packetery\Phpro\SoapClient\Xml\WsdlXml;
use Packetery\Psr\Http\Message\ResponseInterface;
/** @internal */
class DisablePoliciesMiddleware extends Middleware
{
    public function getName() : string
    {
        return 'wsdl_disable_policies';
    }
    public function afterResponse(ResponseInterface $response) : ResponseInterface
    {
        $xml = WsdlXml::fromStream($response->getBody());
        $xml->registerNamespace('wsd', 'http://schemas.xmlsoap.org/ws/2004/09/policy');
        /** @var \DOMElement $node */
        // remove all "UsingPolicy" tags
        foreach ($xml->xpath('//wsd:UsingPolicy') as $node) {
            $node->parentNode->removeChild($node);
        }
        // remove all "Policy" tags
        foreach ($xml->xpath('//wsd:Policy') as $node) {
            $node->parentNode->removeChild($node);
        }
        return $response->withBody($xml->toStream());
    }
}
