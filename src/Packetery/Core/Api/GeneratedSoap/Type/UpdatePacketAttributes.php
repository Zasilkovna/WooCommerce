<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class UpdatePacketAttributes
{

    /**
     * @var float
     */
    private $cod;

    public function getCod()
    {
        return $this->cod;
    }

    public function withCod($cod)
    {
        $new = clone $this;
        $new->cod = $cod;

        return $new;
    }


}

