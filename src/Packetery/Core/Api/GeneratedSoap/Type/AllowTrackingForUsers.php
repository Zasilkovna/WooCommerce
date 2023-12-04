<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class AllowTrackingForUsers
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\ApiKeys
     */
    private $apiKeys;

    public function getApiKeys()
    {
        return $this->apiKeys;
    }

    public function withApiKeys($apiKeys)
    {
        $new = clone $this;
        $new->apiKeys = $apiKeys;

        return $new;
    }


}

