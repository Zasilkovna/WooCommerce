<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class ItemCollection
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\Item
     */
    private $item;

    public function getItem()
    {
        return $this->item;
    }

    public function withItem($item)
    {
        $new = clone $this;
        $new->item = $item;

        return $new;
    }


}

