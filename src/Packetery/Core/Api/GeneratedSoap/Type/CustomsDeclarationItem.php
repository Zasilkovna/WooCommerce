<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CustomsDeclarationItem
{

    /**
     * @var string
     */
    private $customsCode;

    /**
     * @var float
     */
    private $value;

    /**
     * @var string
     */
    private $productNameEn;

    /**
     * @var string
     */
    private $productName;

    /**
     * @var int
     */
    private $unitsCount;

    /**
     * @var string
     */
    private $countryOfOrigin;

    /**
     * @var int
     */
    private $weight;

    /**
     * @var bool
     */
    private $isFoodBook;

    /**
     * @var bool
     */
    private $isVoc;

    public function getCustomsCode()
    {
        return $this->customsCode;
    }

    public function withCustomsCode($customsCode)
    {
        $new = clone $this;
        $new->customsCode = $customsCode;

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

    public function getProductNameEn()
    {
        return $this->productNameEn;
    }

    public function withProductNameEn($productNameEn)
    {
        $new = clone $this;
        $new->productNameEn = $productNameEn;

        return $new;
    }

    public function getProductName()
    {
        return $this->productName;
    }

    public function withProductName($productName)
    {
        $new = clone $this;
        $new->productName = $productName;

        return $new;
    }

    public function getUnitsCount()
    {
        return $this->unitsCount;
    }

    public function withUnitsCount($unitsCount)
    {
        $new = clone $this;
        $new->unitsCount = $unitsCount;

        return $new;
    }

    public function getCountryOfOrigin()
    {
        return $this->countryOfOrigin;
    }

    public function withCountryOfOrigin($countryOfOrigin)
    {
        $new = clone $this;
        $new->countryOfOrigin = $countryOfOrigin;

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

    public function getIsFoodBook()
    {
        return $this->isFoodBook;
    }

    public function withIsFoodBook($isFoodBook)
    {
        $new = clone $this;
        $new->isFoodBook = $isFoodBook;

        return $new;
    }

    public function getIsVoc()
    {
        return $this->isVoc;
    }

    public function withIsVoc($isVoc)
    {
        $new = clone $this;
        $new->isVoc = $isVoc;

        return $new;
    }


}

