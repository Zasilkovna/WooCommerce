<?php

namespace Packetery\Phpro\SoapClient\Wsdl\Provider;

use Packetery\Http\Client\Common\PluginClient;
use Packetery\Http\Client\HttpClient;
use Packetery\Http\Discovery\MessageFactoryDiscovery;
use Packetery\Phpro\SoapClient\Exception\WsdlException;
use Packetery\Phpro\SoapClient\Middleware\MiddlewareInterface;
use Packetery\Phpro\SoapClient\Middleware\MiddlewareSupportingInterface;
use Packetery\Phpro\SoapClient\Util\Filesystem;
/**
 * @deprecated Use the CachedWsdlProvider in combination with the HttpWsdlLoader instead!
 * TODO : this class will be removed in v2.0
 * @internal
 */
class HttPlugWsdlProvider implements WsdlProviderInterface, MiddlewareSupportingInterface
{
    /**
     * @var HttpClient
     */
    private $client;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];
    /**
     * @var string
     */
    private $location = '';
    public function __construct(HttpClient $client, Filesystem $filesystem)
    {
        $this->client = $client;
        $this->filesystem = $filesystem;
    }
    public static function createForClient(HttpClient $client) : HttPlugWsdlProvider
    {
        return new self($client, new Filesystem());
    }
    /**
     * @param MiddlewareInterface $middleware
     */
    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[$middleware->getName()] = $middleware;
    }
    /**
     * @return string
     */
    public function getLocation() : string
    {
        return $this->location;
    }
    /**
     * @param string $location
     */
    public function setLocation(string $location)
    {
        $this->location = $location;
    }
    /**
     * @param string $source
     *
     * @return string
     * @throws \Phpro\SoapClient\Exception\WsdlException
     */
    public function provide(string $source) : string
    {
        $client = new PluginClient($this->client, $this->middlewares);
        $location = $this->getLocation() ?: \tempnam(\sys_get_temp_dir(), 'phpro-soap-client-wsdl');
        if (!$this->filesystem->fileExists($location)) {
            throw WsdlException::notFound($source);
        }
        try {
            $request = MessageFactoryDiscovery::find()->createRequest('GET', $source);
            $response = $client->sendRequest($request);
            $this->filesystem->putFileContents($location, (string) $response->getBody());
        } catch (\Exception $exception) {
            throw WsdlException::fromException($exception);
        }
        return $location;
    }
}
