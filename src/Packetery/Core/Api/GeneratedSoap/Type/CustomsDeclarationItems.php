<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CustomsDeclarationItems
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CustomsDeclarationItem
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

