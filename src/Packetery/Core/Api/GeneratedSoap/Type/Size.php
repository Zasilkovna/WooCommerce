<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class Size
{

    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    public function getLength()
    {
        return $this->length;
    }

    public function withLength($length)
    {
        $new = clone $this;
        $new->length = $length;

        return $new;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function withWidth($width)
    {
        $new = clone $this;
        $new->width = $width;

        return $new;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function withHeight($height)
    {
        $new = clone $this;
        $new->height = $height;

        return $new;
    }


}

