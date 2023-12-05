<?php

namespace Packetery\Phpro\SoapClient\Type;

/**
 * Class MultiArgumentRequestInterface
 *
 * @package Phpro\SoapClient\Type\Legacy
 * @internal
 */
interface MultiArgumentRequestInterface extends RequestInterface
{
    /**
     * @return array
     */
    public function getArguments() : array;
}
