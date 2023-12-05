<?php

namespace Packetery\PhproTest\SoapClient\Unit\Middleware;

use Packetery\GuzzleHttp\Psr7\Request;
use Packetery\GuzzleHttp\Psr7\Response;
use Packetery\Http\Client\Common\PluginClient;
use Packetery\Http\Message\MessageFactory\GuzzleMessageFactory;
use Packetery\Http\Mock\Client;
use Packetery\Phpro\SoapClient\Exception\RuntimeException;
use Packetery\Phpro\SoapClient\Middleware\NtlmMiddleware;
use Packetery\Phpro\SoapClient\Middleware\MiddlewareInterface;
use Packetery\PHPUnit\Framework\TestCase;
/**
 * Class NtlmMiddleware
 *
 * @package PhproTest\SoapClient\Unit\Middleware
 * @internal
 */
class NtlmMiddlewareTest extends TestCase
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
     * @var NtlmMiddleware
     */
    private $middleware;
    /***
     * Initialize all basic objects
     */
    protected function setUp() : void
    {
        $this->middleware = new NtlmMiddleware('username', 'password');
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
        $this->assertEquals('ntlm_middleware', $this->middleware->getName());
    }
    /**
     * @test
     */
    function it_adds_ntlm_auth_to_the_request()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/CURLOPT_HTTPAUTH \\= CURLAUTH_NTLM/i');
        $this->expectExceptionMessageMatches('/CURLOPT_USERPWD \\= "username\\:password"/i');
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/'));
    }
}
