<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class Item
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\AttributeCollection
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

