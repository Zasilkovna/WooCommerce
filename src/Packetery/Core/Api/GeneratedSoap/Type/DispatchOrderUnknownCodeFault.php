<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class DispatchOrderUnknownCodeFault
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\Codes
     */
    private $codes;

    public function getCodes()
    {
        return $this->codes;
    }

    public function withCodes($codes)
    {
        $new = clone $this;
        $new->codes = $codes;

        return $new;
    }


}

