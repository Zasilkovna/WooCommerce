<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketAttributesFault
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\Attributes
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

