<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class PacketAttributes
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $affiliateId;

    /**
     * @var string
     */
    private $number;

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
    private $company;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var int
     */
    private $addressId;

    /**
     * @var float
     */
    private $cod;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var float
     */
    private $value;

    /**
     * @var float
     */
    private $weight;

    /**
     * @var string
     */
    private $eshop;

    /**
     * @var int
     */
    private $adultContent;

    /**
     * @var \DateTimeInterface
     */
    private $deliverOn;

    /**
     * @var string
     */
    private $note;

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
    private $province;

    /**
     * @var string
     */
    private $zip;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\DispatchOrder
     */
    private $dispatchOrder;

    /**
     * @var string
     */
    private $customerBarcode;

    /**
     * @var string
     */
    private $carrierPickupPoint;

    /**
     * @var string
     */
    private $carrierService;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\CustomsDeclaration
     */
    private $customsDeclaration;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\Size
     */
    private $size;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\AttributeCollection
     */
    private $attributes;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\ItemCollection
     */
    private $items;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\Security
     */
    private $security;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\Services
     */
    private $services;

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

    public function getAffiliateId()
    {
        return $this->affiliateId;
    }

    public function withAffiliateId($affiliateId)
    {
        $new = clone $this;
        $new->affiliateId = $affiliateId;

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

    public function getCompany()
    {
        return $this->company;
    }

    public function withCompany($company)
    {
        $new = clone $this;
        $new->company = $company;

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

    public function getAddressId()
    {
        return $this->addressId;
    }

    public function withAddressId($addressId)
    {
        $new = clone $this;
        $new->addressId = $addressId;

        return $new;
    }

    public function getCod()
    {
        return $this->cod;
    }

    public function withCod($cod)
    {
        $new = clone $this;
        $new->cod = $cod;

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

    public function getWeight()
    {
        return $this->weight;
    }

    public function withWeight($weight)
    {
        $new = clone $this;
        $new->weight = $weight;

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

    public function getAdultContent()
    {
        return $this->adultContent;
    }

    public function withAdultContent($adultContent)
    {
        $new = clone $this;
        $new->adultContent = $adultContent;

        return $new;
    }

    public function getDeliverOn()
    {
        return $this->deliverOn;
    }

    public function withDeliverOn($deliverOn)
    {
        $new = clone $this;
        $new->deliverOn = $deliverOn;

        return $new;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function withNote($note)
    {
        $new = clone $this;
        $new->note = $note;

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

    public function getProvince()
    {
        return $this->province;
    }

    public function withProvince($province)
    {
        $new = clone $this;
        $new->province = $province;

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

    public function getDispatchOrder()
    {
        return $this->dispatchOrder;
    }

    public function withDispatchOrder($dispatchOrder)
    {
        $new = clone $this;
        $new->dispatchOrder = $dispatchOrder;

        return $new;
    }

    public function getCustomerBarcode()
    {
        return $this->customerBarcode;
    }

    public function withCustomerBarcode($customerBarcode)
    {
        $new = clone $this;
        $new->customerBarcode = $customerBarcode;

        return $new;
    }

    public function getCarrierPickupPoint()
    {
        return $this->carrierPickupPoint;
    }

    public function withCarrierPickupPoint($carrierPickupPoint)
    {
        $new = clone $this;
        $new->carrierPickupPoint = $carrierPickupPoint;

        return $new;
    }

    public function getCarrierService()
    {
        return $this->carrierService;
    }

    public function withCarrierService($carrierService)
    {
        $new = clone $this;
        $new->carrierService = $carrierService;

        return $new;
    }

    public function getCustomsDeclaration()
    {
        return $this->customsDeclaration;
    }

    public function withCustomsDeclaration($customsDeclaration)
    {
        $new = clone $this;
        $new->customsDeclaration = $customsDeclaration;

        return $new;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function withSize($size)
    {
        $new = clone $this;
        $new->size = $size;

        return $new;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function withAttributes($attributes)
    {
        $new = clone $this;
        $new->attributes = $attributes;

        return $new;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function withItems($items)
    {
        $new = clone $this;
        $new->items = $items;

        return $new;
    }

    public function getSecurity()
    {
        return $this->security;
    }

    public function withSecurity($security)
    {
        $new = clone $this;
        $new->security = $security;

        return $new;
    }

    public function getServices()
    {
        return $this->services;
    }

    public function withServices($services)
    {
        $new = clone $this;
        $new->services = $services;

        return $new;
    }


}

