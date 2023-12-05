<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class ExternalStatusRecord
{

    /**
     * @var \DateTimeInterface
     */
    private $dateTime;

    /**
     * @var string
     */
    private $carrierClass;

    /**
     * @var string
     */
    private $statusCode;

    /**
     * @var string
     */
    private $externalStatusName;

    /**
     * @var string
     */
    private $externalNote;

    /**
     * @var string
     */
    private $externalTrackingCode;

    public function getDateTime()
    {
        return $this->dateTime;
    }

    public function withDateTime($dateTime)
    {
        $new = clone $this;
        $new->dateTime = $dateTime;

        return $new;
    }

    public function getCarrierClass()
    {
        return $this->carrierClass;
    }

    public function withCarrierClass($carrierClass)
    {
        $new = clone $this;
        $new->carrierClass = $carrierClass;

        return $new;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function withStatusCode($statusCode)
    {
        $new = clone $this;
        $new->statusCode = $statusCode;

        return $new;
    }

    public function getExternalStatusName()
    {
        return $this->externalStatusName;
    }

    public function withExternalStatusName($externalStatusName)
    {
        $new = clone $this;
        $new->externalStatusName = $externalStatusName;

        return $new;
    }

    public function getExternalNote()
    {
        return $this->externalNote;
    }

    public function withExternalNote($externalNote)
    {
        $new = clone $this;
        $new->externalNote = $externalNote;

        return $new;
    }

    public function getExternalTrackingCode()
    {
        return $this->externalTrackingCode;
    }

    public function withExternalTrackingCode($externalTrackingCode)
    {
        $new = clone $this;
        $new->externalTrackingCode = $externalTrackingCode;

        return $new;
    }


}

