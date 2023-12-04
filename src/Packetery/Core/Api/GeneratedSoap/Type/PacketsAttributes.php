<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketsAttributes
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\PacketAttributes
     */
    private $attributes;

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function withAttributes($attributes)
    {
        $new = clone $this;
        $new->attributes = $attributes;

        return $new;
    }


}

