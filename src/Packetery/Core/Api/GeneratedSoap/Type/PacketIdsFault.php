<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketIdsFault
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\Ids
     */
    private $ids;

    public function getIds()
    {
        return $this->ids;
    }

    public function withIds($ids)
    {
        $new = clone $this;
        $new->ids = $ids;

        return $new;
    }


}

