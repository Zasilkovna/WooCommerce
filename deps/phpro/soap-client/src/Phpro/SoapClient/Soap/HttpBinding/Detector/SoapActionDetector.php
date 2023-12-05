<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\HttpBinding\Detector;

use Packetery\Http\Client\Exception\RequestException;
use Packetery\Psr\Http\Message\RequestInterface;
/** @internal */
class SoapActionDetector
{
    public static function detectFromRequest(RequestInterface $request) : string
    {
        $header = $request->getHeader('SOAPAction');
        if ($header) {
            return (string) $header[0];
        }
        $contentTypes = $request->getHeader('Content-Type');
        if ($contentTypes) {
            $contentType = $contentTypes[0];
            foreach (\explode(';', $contentType) as $part) {
                if (\strpos($part, 'action=') !== \false) {
                    return \trim(\explode('=', $part)[1], '"\'');
                }
            }
        }
        throw new RequestException('SOAP Action not found in HTTP headers.', $request);
    }
}
