<?php

namespace Packetery\Checkout\Block\Adminhtml\Order;

class GridExport extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Learning\Test\Model\ResourceModel\Info\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Learning\Test\Model\ResourceModel\Info\CollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
		$this->setId('order_items');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
    }

    /**
     * Prepare grid collection object
     *
     * @return $this
     */

    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }


    public function massaction(array $items, $action, $acceptAlert = false, $massActionSelection = '')
    {
        die;
    }

    /**
     * @param string $orderIds
     *
     * @return string
     */
    public function getCsvMassFileContents($orderIds)
    {
        $col = $this->loadDataSelection();

        $col->getSelect()->where("id IN (?)", $orderIds);

        $collection = $col->load();

        $contents = $this->getCsvHeader();

        foreach ($collection as $row)
        {
            $order = $this->getExportRow($row);
            $contents .= "," . implode(',', $order) . PHP_EOL;
        }

        return $contents;
    }

    public function getCsvAllFileContents($onlyNotExported = FALSE)
    {
        $col = $this->loadDataSelection();

        if ($onlyNotExported)
        {
            $col->getSelect()->where('exported = ?', 0);
        }
        $collection = $col->load();
        $contents = $this->getCsvHeader();

        foreach ($collection as $row)
        {
            $order = $this->getExportRow($row);
            $contents .= "," . implode(',', $order) . PHP_EOL;
        }

        return $contents;
    }

    /**
     * Header for CSV file
     */
    protected function getCsvHeader()
    {
        return '"version 5"' . PHP_EOL . PHP_EOL;
    }

    /**
     * Basic for selection of exported data
     */
    protected function loadDataSelection()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $productCollection = $objectManager->create('Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory');

        return $productCollection->create();
    }

    /**
     * Prepare row for CSV export
     */
    protected function getExportRow($row)
    {
        return [
            $row->getData('order_number'),
            $row->getData('recipient_firstname'),
            $row->getData('recipient_lastname'),
            $row->getData('recipient_company'),
            $row->getData('recipient_email'),
            $row->getData('recipient_phone'),
            $row->getData('cod'),
            $row->getData('currency'),
            $row->getData('value'),
            '',
            $row->getData('point_id'),
            $row->getData('sender_label'),
            '',
            '',
            $row->getData('recipient_street'),
            $row->getData('recipient_house_number'),
            $row->getData('recipient_city'),
            $row->getData('recipient_zip'),
            '',
            $row->getData('width'),
            $row->getData('height'),
            $row->getData('depth'),
            ''
        ];
    }


	/**
	 * Custom filtration for export status
	 * @param $collection
	 * @param $column
	 */
	public function filterOptionExport($collection, $column)
	{
		$filterValue = intval($column->getFilter()->getValue());

		if($filterValue === 0)
		{
			$collection->getSelect()->where("exported = '' OR exported IS NULL");
		}

		if($filterValue === 1)
		{
			$collection->getSelect()->where("exported = 1");
		}
	}
}
