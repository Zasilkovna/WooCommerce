<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Wsdl\Loader;

use Packetery\Psr\Http\Client\ClientInterface;
use Packetery\Psr\Http\Message\RequestFactoryInterface;
/** @internal */
final class HttpWsdlLoader implements WsdlLoaderInterface
{
    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;
    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
    }
    public function load(string $wsdl) : string
    {
        $response = $this->client->sendRequest($this->requestFactory->createRequest('GET', $wsdl));
        return (string) $response->getBody();
    }
}
