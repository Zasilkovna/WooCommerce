<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

use Packetery\Phpro\SoapClient\Type\ResultInterface;

class NullableDate implements ResultInterface
{

    /**
     * @var \DateTimeInterface
     */
    private $date;

    public function getDate()
    {
        return $this->date;
    }

    public function withDate($date)
    {
        $new = clone $this;
        $new->date = $date;

        return $new;
    }


}

