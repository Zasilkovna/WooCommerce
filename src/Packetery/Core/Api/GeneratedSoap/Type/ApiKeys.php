<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class ApiKeys
{

    /**
     * @var string
     */
    private $apiKey;

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


}

