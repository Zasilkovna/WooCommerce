<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketIds
{

    /**
     * @var string
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }

    public function withId($id)
    {
        $new = clone $this;
        $new->id = $id;

        return $new;
    }


}

