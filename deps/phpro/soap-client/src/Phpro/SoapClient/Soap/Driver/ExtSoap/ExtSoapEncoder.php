<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap;

use Packetery\Phpro\SoapClient\Soap\Engine\EncoderInterface;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
/** @internal */
class ExtSoapEncoder implements EncoderInterface
{
    /**
     * @var AbusedClient
     */
    private $client;
    public function __construct(AbusedClient $client)
    {
        $this->client = $client;
    }
    public function encode(string $method, array $arguments) : SoapRequest
    {
        try {
            $this->client->__soapCall($method, $arguments);
            $encoded = $this->client->collectRequest();
        } finally {
            $this->client->cleanUpTemporaryState();
        }
        return $encoded;
    }
}
