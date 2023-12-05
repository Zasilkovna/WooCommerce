<?php

namespace Packetery\PhproTest\SoapClient\Unit\Middleware\Wsdl;

use Packetery\GuzzleHttp\Psr7\Request;
use Packetery\GuzzleHttp\Psr7\Response;
use Packetery\Http\Client\Common\PluginClient;
use Packetery\Http\Message\MessageFactory\GuzzleMessageFactory;
use Packetery\Http\Mock\Client;
use Packetery\Phpro\SoapClient\Middleware\MiddlewareInterface;
use Packetery\Phpro\SoapClient\Middleware\Wsdl\DisablePoliciesMiddleware;
use Packetery\Phpro\SoapClient\Xml\WsdlXml;
use Packetery\PHPUnit\Framework\TestCase;
/**
 * Class BasicAuthMiddleware
 *
 * @package PhproTest\SoapClient\Unit\Middleware
 * @internal
 */
class DisablePoliciesMiddlewareTest extends TestCase
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
     * @var DisablePoliciesMiddleware
     */
    private $middleware;
    /***
     * Initialize all basic objects
     */
    protected function setUp() : void
    {
        $this->middleware = new DisablePoliciesMiddleware();
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
        $this->assertEquals('wsdl_disable_policies', $this->middleware->getName());
    }
    /**
     * @test
     */
    function it_removes_wsdl_policies()
    {
        $this->mockClient->addResponse(new Response(200, [], \file_get_contents(FIXTURE_DIR . '/wsdl/wsdl-policies.wsdl')));
        $response = $this->client->sendRequest(new Request('POST', '/'));
        $xml = WsdlXml::fromStream($response->getBody());
        $xml->registerNamespace('wsd', 'http://schemas.xmlsoap.org/ws/2004/09/policy');
        $this->assertEquals(0, $xml->xpath('//wsd:Policy')->length, 'Still got policies in WSDL file.');
        $this->assertEquals(0, $xml->xpath('//wsd:UsingPolicy')->length, 'Still got using statements for policies in WSDL file.');
    }
}
