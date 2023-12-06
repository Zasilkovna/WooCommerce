<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class ReturnDestinationServiceAddress
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $surname;

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
    private $phone;

    /**
     * @var string
     */
    private $email;

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

    public function getSurname()
    {
        return $this->surname;
    }

    public function withSurname($surname)
    {
        $new = clone $this;
        $new->surname = $surname;

        return $new;
    }

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


}

