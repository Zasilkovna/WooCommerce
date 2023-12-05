<?php

namespace Packetery\Phpro\SoapClient\Soap\Handler;

use Packetery\Phpro\SoapClient\Soap\HttpBinding\LastRequestInfo;
/**
 * Class LastRequestInfoCollectorInterface
 *
 * @package Phpro\SoapClient\Soap\HttpBinding
 * @internal
 */
interface LastRequestInfoCollectorInterface
{
    /***
     * @return LastRequestInfo
     */
    public function collectLastRequestInfo() : LastRequestInfo;
}
