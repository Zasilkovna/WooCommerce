<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class Services
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\FirstMileCarrierService
     */
    private $firstMileCarrier;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\LastMileCarrierService
     */
    private $lastMileCarrier;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\ReturnDestinationService
     */
    private $returnDestination;

    public function getFirstMileCarrier()
    {
        return $this->firstMileCarrier;
    }

    public function withFirstMileCarrier($firstMileCarrier)
    {
        $new = clone $this;
        $new->firstMileCarrier = $firstMileCarrier;

        return $new;
    }

    public function getLastMileCarrier()
    {
        return $this->lastMileCarrier;
    }

    public function withLastMileCarrier($lastMileCarrier)
    {
        $new = clone $this;
        $new->lastMileCarrier = $lastMileCarrier;

        return $new;
    }

    public function getReturnDestination()
    {
        return $this->returnDestination;
    }

    public function withReturnDestination($returnDestination)
    {
        $new = clone $this;
        $new->returnDestination = $returnDestination;

        return $new;
    }


}

