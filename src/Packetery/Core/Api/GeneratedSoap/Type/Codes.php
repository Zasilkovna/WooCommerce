<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class Codes
{

    /**
     * @var string
     */
    private $code;

    public function getCode()
    {
        return $this->code;
    }

    public function withCode($code)
    {
        $new = clone $this;
        $new->code = $code;

        return $new;
    }


}

