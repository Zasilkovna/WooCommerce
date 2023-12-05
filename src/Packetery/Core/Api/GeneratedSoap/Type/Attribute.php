<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class Attribute
{

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $value;

    public function getKey()
    {
        return $this->key;
    }

    public function withKey($key)
    {
        $new = clone $this;
        $new->key = $key;

        return $new;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function withValue($value)
    {
        $new = clone $this;
        $new->value = $value;

        return $new;
    }


}

