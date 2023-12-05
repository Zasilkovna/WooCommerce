<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class Address
{

    /**
     * @var string
     */
    private $street;

    /**
     * @var string
     */
    private $houseNumber;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $zip;

    /**
     * @var string
     */
    private $countryCode;

    public function getStreet()
    {
        return $this->street;
    }

    public function withStreet($street)
    {
        $new = clone $this;
        $new->street = $street;

        return $new;
    }

    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    public function withHouseNumber($houseNumber)
    {
        $new = clone $this;
        $new->houseNumber = $houseNumber;

        return $new;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function withCity($city)
    {
        $new = clone $this;
        $new->city = $city;

        return $new;
    }

    public function getZip()
    {
        return $this->zip;
    }

    public function withZip($zip)
    {
        $new = clone $this;
        $new->zip = $zip;

        return $new;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function withCountryCode($countryCode)
    {
        $new = clone $this;
        $new->countryCode = $countryCode;

        return $new;
    }


}

