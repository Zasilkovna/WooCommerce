<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class AttributeCollection
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\Attribute
     */
    private $attribute;

    public function getAttribute()
    {
        return $this->attribute;
    }

    public function withAttribute($attribute)
    {
        $new = clone $this;
        $new->attribute = $attribute;

        return $new;
    }


}

