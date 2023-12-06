<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Middleware\WSICompliance;

use Packetery\Http\Promise\Promise;
use Packetery\Phpro\SoapClient\Middleware\Middleware;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\Detector\SoapActionDetector;
use Packetery\Psr\Http\Message\RequestInterface;
/**
 * @see http://www.ws-i.org/Profiles/BasicProfile-1.0-2004-04-16.html#R2744
 *
 * Fixes error:
 *
 *  WS-I Compliance failure (R2744):
 *  The value of the SOAPAction transport header must be double-quoted.
 * @internal
 */
class QuotedSoapActionMiddleware extends Middleware
{
    public function getName() : string
    {
        return 'WS-I-compliance-quoted_soap_action_middleware';
    }
    public function beforeRequest(callable $next, RequestInterface $request) : Promise
    {
        $soapAction = SoapActionDetector::detectFromRequest($request);
        $soapAction = \trim($soapAction ?? '', '"\'');
        return $next($request->withHeader('SOAPAction', '"' . $soapAction . '"'));
    }
}
