<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class StorageFileAttributes
{

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $name;

    public function getContent()
    {
        return $this->content;
    }

    public function withContent($content)
    {
        $new = clone $this;
        $new->content = $content;

        return $new;
    }

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


}

