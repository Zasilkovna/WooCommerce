<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\HttpBinding\Converter;

use Packetery\Http\Message\Formatter\FullHttpMessageFormatter;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\LastRequestInfo;
use Packetery\Psr\Http\Message\RequestInterface;
use Packetery\Psr\Http\Message\ResponseInterface;
/** @internal */
class Psr7ToLastRequestInfoConverter
{
    public function convert(RequestInterface $request, ResponseInterface $response)
    {
        // Reset the bodies:
        $request->getBody()->rewind();
        $response->getBody()->rewind();
        $formatter = new FullHttpMessageFormatter(null);
        $requestString = $formatter->formatRequest($request);
        $responseString = $formatter->formatResponse($response);
        $requestHeaders = '';
        $requestBody = '';
        $responseHeaders = '';
        $responseBody = '';
        if ($requestString) {
            $requestParts = \explode("\n\n", \substr($requestString, \strpos($requestString, "\n") + 1), 2);
            $requestHeaders = \trim($requestParts[0] ?? '');
            $requestBody = (string) $request->getBody();
        }
        if ($responseString) {
            $responseParts = \explode("\n\n", \substr($responseString, \strpos($responseString, "\n") + 1), 2);
            $responseHeaders = \trim($responseParts[0] ?? '');
            $responseBody = (string) $response->getBody();
        }
        // Reset the bodies:
        $request->getBody()->rewind();
        $response->getBody()->rewind();
        return new LastRequestInfo($requestHeaders, $requestBody, $responseHeaders, $responseBody);
    }
}
