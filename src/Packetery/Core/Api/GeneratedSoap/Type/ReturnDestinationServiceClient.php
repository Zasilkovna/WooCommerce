<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class ReturnDestinationServiceClient
{

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $eshop;

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function withApiKey($apiKey)
    {
        $new = clone $this;
        $new->apiKey = $apiKey;

        return $new;
    }

    public function getEshop()
    {
        return $this->eshop;
    }

    public function withEshop($eshop)
    {
        $new = clone $this;
        $new->eshop = $eshop;

        return $new;
    }


}

