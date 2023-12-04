<?php

namespace Packetery\Phpro\SoapClient\Middleware;

use Packetery\Http\Promise\Promise;
use Packetery\Phpro\SoapClient\Soap\Handler\LastRequestInfoCollectorInterface;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\Converter\Psr7ToLastRequestInfoConverter;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\LastRequestInfo;
use Packetery\Psr\Http\Message\RequestInterface;
use Packetery\Psr\Http\Message\ResponseInterface;
/** @internal */
class CollectLastRequestInfoMiddleware extends Middleware implements LastRequestInfoCollectorInterface
{
    /**
     * @var RequestInterface|null
     */
    private $lastRequest;
    /**
     * @var ResponseInterface|null
     */
    private $lastResponse;
    public function getName() : string
    {
        return 'collect_last_request_info_middleware';
    }
    public function beforeRequest(callable $handler, RequestInterface $request) : Promise
    {
        $this->lastRequest = $request;
        $this->lastResponse = null;
        return $handler($request);
    }
    public function afterResponse(ResponseInterface $response) : ResponseInterface
    {
        $this->lastResponse = $response;
        return $response;
    }
    public function collectLastRequestInfo() : LastRequestInfo
    {
        if (!$this->lastRequest || !$this->lastResponse) {
            return LastRequestInfo::createEmpty();
        }
        return (new Psr7ToLastRequestInfoConverter())->convert($this->lastRequest, $this->lastResponse);
    }
}
