<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketConsignerDetail
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $consignerEmail;

    /**
     * @var string
     */
    private $consignerPhone;

    /**
     * @var string
     */
    private $consignerCountry;

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

    public function getPassword()
    {
        return $this->password;
    }

    public function withPassword($password)
    {
        $new = clone $this;
        $new->password = $password;

        return $new;
    }

    public function getConsignerEmail()
    {
        return $this->consignerEmail;
    }

    public function withConsignerEmail($consignerEmail)
    {
        $new = clone $this;
        $new->consignerEmail = $consignerEmail;

        return $new;
    }

    public function getConsignerPhone()
    {
        return $this->consignerPhone;
    }

    public function withConsignerPhone($consignerPhone)
    {
        $new = clone $this;
        $new->consignerPhone = $consignerPhone;

        return $new;
    }

    public function getConsignerCountry()
    {
        return $this->consignerCountry;
    }

    public function withConsignerCountry($consignerCountry)
    {
        $new = clone $this;
        $new->consignerCountry = $consignerCountry;

        return $new;
    }


}

