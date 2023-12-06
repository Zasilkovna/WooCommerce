<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class DispatchOrder2Item
{

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $unit_price;

    /**
     * @var string
     */
    private $pieces;

    /**
     * @var string
     */
    private $price;

    /**
     * @var string
     */
    private $vat;

    /**
     * @var string
     */
    private $price_vat;

    public function getCode()
    {
        return $this->code;
    }

    public function withCode($code)
    {
        $new = clone $this;
        $new->code = $code;

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

    public function getUnit_price()
    {
        return $this->unit_price;
    }

    public function withUnit_price($unit_price)
    {
        $new = clone $this;
        $new->unit_price = $unit_price;

        return $new;
    }

    public function getPieces()
    {
        return $this->pieces;
    }

    public function withPieces($pieces)
    {
        $new = clone $this;
        $new->pieces = $pieces;

        return $new;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function withPrice($price)
    {
        $new = clone $this;
        $new->price = $price;

        return $new;
    }

    public function getVat()
    {
        return $this->vat;
    }

    public function withVat($vat)
    {
        $new = clone $this;
        $new->vat = $vat;

        return $new;
    }

    public function getPrice_vat()
    {
        return $this->price_vat;
    }

    public function withPrice_vat($price_vat)
    {
        $new = clone $this;
        $new->price_vat = $price_vat;

        return $new;
    }


}

