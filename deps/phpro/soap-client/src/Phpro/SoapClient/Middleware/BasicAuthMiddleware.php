<?php

namespace Packetery\Phpro\SoapClient\Middleware;

use Packetery\Http\Promise\Promise;
use Packetery\Psr\Http\Message\RequestInterface;
/** @internal */
class BasicAuthMiddleware extends Middleware
{
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
    public function getName() : string
    {
        return 'basic_auth_middleware';
    }
    public function beforeRequest(callable $handler, RequestInterface $request) : Promise
    {
        $request = $request->withHeader('Authorization', \sprintf('Basic %s', \base64_encode(\sprintf('%s:%s', $this->username, $this->password))));
        return $handler($request);
    }
}
