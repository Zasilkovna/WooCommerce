<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class StatusRecords implements ResultInterface
{

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\StatusRecord
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

