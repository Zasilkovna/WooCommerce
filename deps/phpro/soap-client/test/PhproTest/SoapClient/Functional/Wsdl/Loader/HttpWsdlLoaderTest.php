<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Functional\Wsdl;

use Packetery\Phpro\SoapClient\Wsdl\Loader\HttpWsdlLoader;
use Packetery\Http\Discovery\Psr17FactoryDiscovery;
use Packetery\Http\Message\RequestMatcher\RequestMatcher;
use Packetery\Http\Mock\Client;
use Packetery\Phpro\SoapClient\Wsdl\Loader\WsdlLoaderInterface;
use Packetery\Phpro\SoapClient\Wsdl\Provider\WsdlProviderInterface;
use Packetery\PHPUnit\Framework\TestCase;
/** @internal */
class HttpWsdlLoaderTest extends TestCase
{
    /**
     * @var Client
     */
    private $client;
    /**
     * @var HttpWsdlLoader
     */
    private $loader;
    protected function setUp() : void
    {
        $this->loader = new HttpWsdlLoader($this->client = new Client(), Psr17FactoryDiscovery::findRequestFactory());
    }
    /** @test */
    public function it_is_a_wsdl_loader() : void
    {
        self::assertInstanceOf(WsdlLoaderInterface::class, $this->loader);
    }
    /** @test */
    public function it_can_load_wsdl() : void
    {
        $url = Psr17FactoryDiscovery::findUrlFactory()->createUri('http://localhost/some/service?wsdl');
        $matcher = new RequestMatcher($url->getPath(), $url->getHost(), ['GET'], ['http']);
        $response = Psr17FactoryDiscovery::findResponseFactory()->createResponse()->withBody(Psr17FactoryDiscovery::findStreamFactory()->createStream($body = 'wsdl body'));
        $this->client->on($matcher, $response);
        self::assertSame($body, $this->loader->load((string) $url));
    }
}
