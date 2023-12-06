<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class SenderGetReturnRoutingResult implements ResultInterface
{

    /**
     * @var string
     */
    private $routingSegment;

    public function getRoutingSegment()
    {
        return $this->routingSegment;
    }

    public function withRoutingSegment($routingSegment)
    {
        $new = clone $this;
        $new->routingSegment = $routingSegment;

        return $new;
    }


}

