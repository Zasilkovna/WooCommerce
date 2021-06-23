<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Pricingrule;

use Magento\Ui\DataProvider\AbstractDataProvider;

class CarrierDataProvider extends AbstractDataProvider
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Carrier\Collection */
    protected $collection;

    /** @var \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier */
    private $modifier;

    /**
     * CarrierDataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $collectionFactory
     * @param \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        \Packetery\Checkout\Model\ResourceModel\Carrier\CollectionFactory $collectionFactory,
        \Packetery\Checkout\Ui\Component\CarrierCountry\Form\Modifier $modifier,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->modifier = $modifier;
    }

    /**
     * @return array
     */
    public function getData(): array {
        return $this->modifier->modifyData(parent::getData());
    }

    /**
     * @return array
     */
    public function getMeta(): array {
        return $this->modifier->modifyMeta(parent::getMeta());
    }
}
