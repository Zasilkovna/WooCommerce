<?php

namespace Packetery\PhproTest\SoapClient\Unit\Middleware;

use Packetery\GuzzleHttp\Psr7\Request;
use Packetery\GuzzleHttp\Psr7\Response;
use Packetery\Http\Client\Common\PluginClient;
use Packetery\Http\Message\MessageFactory\GuzzleMessageFactory;
use Packetery\Http\Mock\Client;
use Packetery\Phpro\SoapClient\Middleware\Middleware;
use Packetery\Phpro\SoapClient\Middleware\MiddlewareInterface;
use Packetery\PHPUnit\Framework\TestCase;
/**
 * Class BasicAuthMiddleware
 *
 * @package PhproTest\SoapClient\Unit\Middleware
 * @internal
 */
class MiddlewareTest extends TestCase
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
     * @var Middleware
     */
    private $middleware;
    /***
     * Initialize all basic objects
     */
    protected function setUp() : void
    {
        $this->middleware = new Middleware();
        $this->mockClient = new Client(new GuzzleMessageFactory());
        $this->client = new PluginClient($this->mockClient, [$this->middleware]);
    }
    /**
     * @test
     */
    function it_is_a_middleware()
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }
    /**
     * @test
     */
    function it_has_a_name()
    {
        $this->assertEquals('empty_middleware', $this->middleware->getName());
    }
    /**
     * @test
     */
    function it_applies_middleware_callbacks()
    {
        $this->mockClient->addResponse($response = new Response());
        $receivedResponse = $this->client->sendRequest($request = new Request('POST', '/', ['User-Agent' => 'no']));
        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertEquals($request, $sentRequest);
        $this->assertEquals($response, $receivedResponse);
    }
}
