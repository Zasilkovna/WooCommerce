<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class StorageFiles implements ResultInterface
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\StorageFile
     */
    private $StorageFile;

    public function getStorageFile()
    {
        return $this->StorageFile;
    }

    public function withStorageFile($StorageFile)
    {
        $new = clone $this;
        $new->StorageFile = $StorageFile;

        return $new;
    }


}

