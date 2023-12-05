<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class ExternalStatusRecords implements ResultInterface
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\ExternalStatusRecord
     */
    private $record;

    public function getRecord()
    {
        return $this->record;
    }

    public function withRecord($record)
    {
        $new = clone $this;
        $new->record = $record;

        return $new;
    }


}

