<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class ClaimAttributes
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var float
     */
    private $value;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $eshop;

    /**
     * @var bool
     */
    private $sendLabelToEmail;

    public function getId()
    {
        return $this->id;
    }

    public function withId($id)
    {
        $new = clone $this;
        $new->id = $id;

        return $new;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function withNumber($number)
    {
        $new = clone $this;
        $new->number = $number;

        return $new;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function withEmail($email)
    {
        $new = clone $this;
        $new->email = $email;

        return $new;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function withPhone($phone)
    {
        $new = clone $this;
        $new->phone = $phone;

        return $new;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function withValue($value)
    {
        $new = clone $this;
        $new->value = $value;

        return $new;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function withCurrency($currency)
    {
        $new = clone $this;
        $new->currency = $currency;

        return $new;
    }

    public function getEshop()
    {
        return $this->eshop;
    }

    public function withEshop($eshop)
    {
        $new = clone $this;
        $new->eshop = $eshop;

        return $new;
    }

    public function getSendLabelToEmail()
    {
        return $this->sendLabelToEmail;
    }

    public function withSendLabelToEmail($sendLabelToEmail)
    {
        $new = clone $this;
        $new->sendLabelToEmail = $sendLabelToEmail;

        return $new;
    }


}

