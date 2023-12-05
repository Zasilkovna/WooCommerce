<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class AttributeFault
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $fault;

    /**
     * @var string
     */
    private $field;

    public function getName()
    {
        return $this->name;
    }

    public function withName($name)
    {
        $new = clone $this;
        $new->name = $name;

        return $new;
    }

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

    public function getField()
    {
        return $this->field;
    }

    public function withField($field)
    {
        $new = clone $this;
        $new->field = $field;

        return $new;
    }


}

