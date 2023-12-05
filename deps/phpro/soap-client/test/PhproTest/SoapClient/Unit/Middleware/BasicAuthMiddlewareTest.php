<?php

namespace Packetery\PhproTest\SoapClient\Unit\Middleware;

use Packetery\GuzzleHttp\Psr7\Request;
use Packetery\GuzzleHttp\Psr7\Response;
use Packetery\Http\Client\Common\PluginClient;
use Packetery\Http\Message\MessageFactory\GuzzleMessageFactory;
use Packetery\Http\Mock\Client;
use Packetery\Phpro\SoapClient\Middleware\BasicAuthMiddleware;
use Packetery\Phpro\SoapClient\Middleware\MiddlewareInterface;
use Packetery\PHPUnit\Framework\TestCase;
/**
 * Class BasicAuthMiddleware
 *
 * @package PhproTest\SoapClient\Unit\Middleware
 * @internal
 */
class BasicAuthMiddlewareTest extends TestCase
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
     * @var BasicAuthMiddleware
     */
    private $middleware;
    /***
     * Initialize all basic objects
     */
    protected function setUp() : void
    {
        $this->middleware = new BasicAuthMiddleware('username', 'password');
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
        $this->assertEquals('basic_auth_middleware', $this->middleware->getName());
    }
    /**
     * @test
     */
    function it_adds_basic_auth_to_the_request()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/'));
        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertEquals(\sprintf('Basic %s', \base64_encode('username:password')), $sentRequest->getHeader('Authorization')[0]);
    }
}
