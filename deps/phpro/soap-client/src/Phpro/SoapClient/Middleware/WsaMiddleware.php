<?php

namespace Packetery\Phpro\SoapClient\Middleware;

use Packetery\Http\Promise\Promise;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\Detector\SoapActionDetector;
use Packetery\Phpro\SoapClient\Xml\SoapXml;
use Packetery\Psr\Http\Message\RequestInterface;
use Packetery\RobRichards\WsePhp\WSASoap;
/** @internal */
class WsaMiddleware extends Middleware
{
    const WSA_ADDRESS_ANONYMOUS = 'http://schemas.xmlsoap.org/ws/2004/08/addressing/role/anonymous';
    private $address;
    public function __construct(string $address = self::WSA_ADDRESS_ANONYMOUS)
    {
        $this->address = $address;
    }
    public function getName() : string
    {
        return 'wsa_middleware';
    }
    public function beforeRequest(callable $handler, RequestInterface $request) : Promise
    {
        $xml = SoapXml::fromStream($request->getBody());
        $wsa = new WSASoap($xml->getXmlDocument());
        $wsa->addAction(SoapActionDetector::detectFromRequest($request));
        $wsa->addTo((string) $request->getUri());
        $wsa->addMessageID();
        $wsa->addReplyTo($this->address);
        $request = $request->withBody($xml->toStream());
        return $handler($request);
    }
}
