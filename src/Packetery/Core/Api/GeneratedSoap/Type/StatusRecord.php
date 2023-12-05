<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class StatusRecord
{

    /**
     * @var \DateTimeInterface
     */
    private $dateTime;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $codeText;

    /**
     * @var string
     */
    private $statusText;

    /**
     * @var int
     */
    private $branchId;

    /**
     * @var int
     */
    private $destinationBranchId;

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

    public function getCodeText()
    {
        return $this->codeText;
    }

    public function withCodeText($codeText)
    {
        $new = clone $this;
        $new->codeText = $codeText;

        return $new;
    }

    public function getStatusText()
    {
        return $this->statusText;
    }

    public function withStatusText($statusText)
    {
        $new = clone $this;
        $new->statusText = $statusText;

        return $new;
    }

    public function getBranchId()
    {
        return $this->branchId;
    }

    public function withBranchId($branchId)
    {
        $new = clone $this;
        $new->branchId = $branchId;

        return $new;
    }

    public function getDestinationBranchId()
    {
        return $this->destinationBranchId;
    }

    public function withDestinationBranchId($destinationBranchId)
    {
        $new = clone $this;
        $new->destinationBranchId = $destinationBranchId;

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

