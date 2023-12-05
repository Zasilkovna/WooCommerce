<?php

namespace Packetery\Phpro\SoapClient\Event;

use Packetery\Phpro\SoapClient\Client;
use Packetery\Phpro\SoapClient\Type\RequestInterface;
/**
 * Class RequestEvent
 *
 * @package Phpro\SoapClient\Event
 * @internal
 */
class RequestEvent extends SoapEvent
{
    /**
     * @var string
     */
    protected $method;
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @param Client $client
     * @param string $method
     * @param RequestInterface $request
     */
    public function __construct(Client $client, string $method, RequestInterface $request)
    {
        $this->client = $client;
        $this->method = $method;
        $this->request = $request;
    }
    /**
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }
    /**
     * @return RequestInterface
     */
    public function getRequest() : RequestInterface
    {
        return $this->request;
    }
    /**
     * @return Client
     */
    public function getClient() : Client
    {
        return $this->client;
    }
    public function registerRequest(RequestInterface $request) : void
    {
        $this->request = $request;
    }
}
