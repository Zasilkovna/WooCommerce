<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class Attributes
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\AttributeFault
     */
    private $fault;

    public function getFault()
    {
        return $this->fault;
    }

    public function withFault($fault)
    {
        $new = clone $this;
        $new->fault = $fault;

        return $new;
    }


}

