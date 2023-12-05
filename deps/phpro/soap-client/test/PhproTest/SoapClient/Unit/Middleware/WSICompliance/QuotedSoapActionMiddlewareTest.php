<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Unit\Middleware\WSICompliance;

use Packetery\GuzzleHttp\Psr7\Request;
use Packetery\GuzzleHttp\Psr7\Response;
use Packetery\Http\Client\Common\PluginClient;
use Packetery\Http\Mock\Client;
use Packetery\Phpro\SoapClient\Middleware\MiddlewareInterface;
use Packetery\Phpro\SoapClient\Middleware\WSICompliance\QuotedSoapActionMiddleware;
use Packetery\PHPUnit\Framework\TestCase;
/** @internal */
class QuotedSoapActionMiddlewareTest extends TestCase
{
    /**
     * @var PluginClient
     */
    private $client;
    /**
     * @var Client
     */
    private $mockClient;
    /**
     * @var QuotedSoapActionMiddleware
     */
    private $middleware;
    /***
     * Initialize all basic objects
     */
    protected function setUp() : void
    {
        $this->middleware = new QuotedSoapActionMiddleware();
        $this->mockClient = new Client();
        $this->client = new PluginClient($this->mockClient, [$this->middleware]);
    }
    /**
     * @test
     */
    public function it_is_a_middleware()
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }
    /**
     * @test
     */
    public function it_has_a_name()
    {
        $this->assertEquals('WS-I-compliance-quoted_soap_action_middleware', $this->middleware->getName());
    }
    /**
     * @test
     */
    public function it_wraps_the_action_with_quotes()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => 'action']));
        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertSame('"action"', $sentRequest->getHeader('SOAPAction')[0]);
    }
    /**
     * @test
     */
    public function it_keeps_the_action_quoted()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => '"action"']));
        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertSame('"action"', $sentRequest->getHeader('SOAPAction')[0]);
    }
    /**
     * @test
     */
    public function it_transforms_single_quotes()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => "'action'"]));
        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertSame('"action"', $sentRequest->getHeader('SOAPAction')[0]);
    }
}
