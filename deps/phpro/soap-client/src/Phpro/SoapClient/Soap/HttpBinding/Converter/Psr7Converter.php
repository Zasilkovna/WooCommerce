<?php

namespace Packetery\Phpro\SoapClient\Soap\HttpBinding\Converter;

use Packetery\Http\Message\MessageFactory;
use Packetery\Http\Message\StreamFactory;
use Packetery\Phpro\SoapClient\Exception\RequestException;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\Builder\Psr7RequestBuilder;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapResponse;
use Packetery\Psr\Http\Message\RequestFactoryInterface;
use Packetery\Psr\Http\Message\RequestInterface;
use Packetery\Psr\Http\Message\ResponseInterface;
use Packetery\Psr\Http\Message\StreamFactoryInterface;
/**
 * Class Psr7Converter
 *
 * @package Phpro\SoapClient\Soap\HttpBinding\Converter
 * @internal
 */
class Psr7Converter
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;
    /**
     * @var StreamFactory
     */
    private $streamFactory;
    public function __construct(MessageFactory $messageFactory, StreamFactory $streamFactory)
    {
        $this->messageFactory = $messageFactory;
        $this->streamFactory = $streamFactory;
    }
    /**
     * @param SoapRequest $request
     *
     * @throws RequestException
     * @return RequestInterface
     */
    public function convertSoapRequest(SoapRequest $request) : RequestInterface
    {
        $builder = new Psr7RequestBuilder($this->messageFactory, $this->streamFactory);
        $request->isSOAP11() ? $builder->isSOAP11() : $builder->isSOAP12();
        $builder->setEndpoint($request->getLocation());
        $builder->setSoapAction($request->getAction());
        $builder->setSoapMessage($request->getRequest());
        return $builder->getHttpRequest();
    }
    /**
     * @param ResponseInterface $response
     *
     * @return SoapResponse
     */
    public function convertSoapResponse(ResponseInterface $response) : SoapResponse
    {
        return new SoapResponse((string) $response->getBody());
    }
}
