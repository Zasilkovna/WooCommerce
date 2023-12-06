<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class ListStorageFileAttributes
{

    /**
     * @var \DateTimeInterface
     */
    private $fromDate;

    /**
     * @var \DateTimeInterface
     */
    private $toDate;

    public function getFromDate()
    {
        return $this->fromDate;
    }

    public function withFromDate($fromDate)
    {
        $new = clone $this;
        $new->fromDate = $fromDate;

        return $new;
    }

    public function getToDate()
    {
        return $this->toDate;
    }

    public function withToDate($toDate)
    {
        $new = clone $this;
        $new->toDate = $toDate;

        return $new;
    }


}

