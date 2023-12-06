<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class ReturnDestinationService
{

    /**
     * @var int
     */
    private $addressId;

    /**
     * @var string
     */
    private $carrierPickupPoint;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\ReturnDestinationServiceAddress
     */
    private $returnAddress;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\ReturnDestinationServiceClient
     */
    private $client;

    public function getAddressId()
    {
        return $this->addressId;
    }

    public function withAddressId($addressId)
    {
        $new = clone $this;
        $new->addressId = $addressId;

        return $new;
    }

    public function getCarrierPickupPoint()
    {
        return $this->carrierPickupPoint;
    }

    public function withCarrierPickupPoint($carrierPickupPoint)
    {
        $new = clone $this;
        $new->carrierPickupPoint = $carrierPickupPoint;

        return $new;
    }

    public function getReturnAddress()
    {
        return $this->returnAddress;
    }

    public function withReturnAddress($returnAddress)
    {
        $new = clone $this;
        $new->returnAddress = $returnAddress;

        return $new;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function withClient($client)
    {
        $new = clone $this;
        $new->client = $client;

        return $new;
    }


}

