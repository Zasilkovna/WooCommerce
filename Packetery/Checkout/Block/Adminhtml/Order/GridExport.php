<?php

namespace Packetery\Checkout\Block\Adminhtml\Order;

use Magento\Framework\Pricing\PriceCurrencyInterface;

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
     * @return string|null
     */
    public function getCsvMassFileContents($orderIds): ?string
    {
        $col = $this->loadDataSelection();

        $col->getSelect()->where("id IN (?)", $orderIds);

        $collection = $col->load();

        return $this->createCsvContent($collection);
    }

    /**
     * @param false $onlyNotExported
     * @return string|null
     */
    public function getCsvAllFileContents($onlyNotExported = FALSE): ?string
    {
        $col = $this->loadDataSelection();

        if ($onlyNotExported)
        {
            $col->getSelect()->where('exported = ?', 0);
        }
        $collection = $col->load();

        return $this->createCsvContent($collection);
    }

    /**
     * Basic for selection of exported data
     * @return \Packetery\Checkout\Model\ResourceModel\Order\Collection
     */
    protected function loadDataSelection()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $productCollection = $objectManager->create('Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory');

        return $productCollection->create();
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function formatNumber($value): string
    {
        if (is_numeric($value)) {
            return number_format((float)$value, PriceCurrencyInterface::DEFAULT_PRECISION, '.', '');
        }

        return '';
    }

    /**
     * Prepare row for CSV export
     */
    protected function getExportRow($row)
    {
        return [
            '',
            $row->getData('order_number'),
            $row->getData('recipient_firstname'),
            $row->getData('recipient_lastname'),
            $row->getData('recipient_company'),
            $row->getData('recipient_email'),
            $row->getData('recipient_phone'),
            $this->formatNumber($row->getData('cod')),
            $row->getData('currency'),
            $this->formatNumber($row->getData('value')),
            '',
            $row->getData('point_id'),
            $row->getData('sender_label'),
            '',
            '',
            $row->getData('recipient_street'),
            $row->getData('recipient_house_number'),
            $row->getData('recipient_city'),
            $row->getData('recipient_zip'),
            $row->getData('carrier_pickup_point'),
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

    /**
     * @param iterable $collection
     * @return string|null
     */
    private function createCsvContent(iterable $collection): ?string
    {
        // Write to memory (unless buffer exceeds limit then it will write to /tmp)
        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, ['version 5']);
        fputcsv($fp, []);
        foreach ($collection as $row) {
            $fields = $this->getExportRow($row);
            fputcsv($fp, $fields);
        }
        rewind($fp); // Set the pointer back to the start
        $contents = stream_get_contents($fp); // Fetch the contents of our CSV
        fclose($fp);
        return ($contents ?: null); // Close our pointer and free up memory and /tmp space
    }
}
