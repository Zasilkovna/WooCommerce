<?php

namespace Packetery\Checkout\Block\Adminhtml\Order;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Learning\Test\Model\ResourceModel\Info\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status\Collection $statusCollectionFactory,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->statusCollectionFactory = $statusCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
		$this->setId('order_items');
		$this->setDefaultSort('order_number');
        $this->setDefaultDir('desc');

		$this->setSaveParametersInSession(false);

		parent::_construct();
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

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('order_id');

        $this->getMassactionBlock()->addItem('export', array(
            'label'=> __('CSV Export'),
            'url'  => $this->getUrl('*/*/exportMass', array('' => ''))
        ));

        return $this;
    }

    /**
     * Prepare default grid column
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

		$this->addColumn('order_number', array(
			'header' => __('Order number'),
			'sortable' => true,
            'index' => 'order_number',
			'renderer' => 'Packetery\Checkout\Block\Adminhtml\Order\Renderer\OrderReference'
		));

        $this->addColumn('created_at', array(
            'header' => __('Purchase Date'),
            'sortable' => false,
            'is_system' => true,
            'filter' => false,
            'renderer' => 'Packetery\Checkout\Block\Adminhtml\Order\Renderer\CreatedDate'
        ));

		$this->addColumn('order_status', array(
			'header' => __('Order status'),
			'sortable' => false,
            'type' => 'options',
            'options'   => self::getOrderStatues(),
            'filter_condition_callback' => [$this, 'filterOrderStatus'],
			'renderer' => 'Packetery\Checkout\Block\Adminhtml\Order\Renderer\OrderStatus'
		));

        $this->addColumn('recipient_lastname', array(
            'header' => __('Full name'),
            'sortable' => true,
            'index' => 'recipient_lastname',
            'filter_condition_callback' => [$this, 'filterOptionRecipientName'],
            'renderer' => 'Packetery\Checkout\Block\Adminhtml\Order\Renderer\Name',
        ));

		$this->addColumn('recipient_company', array(
			'header' => __('Recipient company'),
			'sortable' => true,
			'index' => 'recipient_company'
		));

		$this->addColumn('recipient_email', array(
			'header' => __('Recipient Email'),
			'sortable' => true,
			'index' => 'recipient_email'
		));

		$this->addColumn('recipient_phone', array(
			'header' => __('Recipient phone number'),
			'sortable' => true,
			'index' => 'recipient_phone'
		));

        $this->addColumn('recipient_address', array(
            'header' => __('Recipient address'),
            'sortable' => true,
            'index' => 'recipient_street',
            'filter_condition_callback' => array($this, 'filterRecipientAddress'),
            'renderer' => 'Packetery\Checkout\Block\Adminhtml\Order\Renderer\Address'
        ));

		$this->addColumn('cod', array(
			'header' => __('Cash on delivery'),
			'sortable' => false,
			'type' => 'options',
			'options'   => self::getOptionArray(),
			'renderer' => 'Packetery\Checkout\Block\Adminhtml\Order\Renderer\CodState',
			'filter_condition_callback' => array($this, 'filterOptionCod')
		));

		$this->addColumn('currency', array(
			'header' => __('Currency'),
			'sortable' => true,
			'index' => 'currency'
		));

		$this->addColumn('value', array(
			'header' => __('Total price'),
			'sortable' => true,
			'type' => 'number',
			'index' => 'value',
		));

        $this->addColumn('point_name', array(
            'header' => __('Pickup point address'),
            'sortable' => false,
            'index' => 'point_name',
            'filter_condition_callback' => array($this, 'filterPointName'),
            'renderer' => 'Packetery\Checkout\Block\Adminhtml\Order\Renderer\DeliveryDestination'
        ));

        $this->addColumn('exported', array(
			'header' => __('Exported'),
            'sortable' => true,
			'index' => 'exported',
			'type' => 'options',
			'options'   => self::getOptionArray(),
            'renderer' => 'Packetery\Checkout\Block\Adminhtml\Order\Renderer\ExportState',
			'filter_condition_callback' => array($this, 'filterOptionExport')
        ));

        $this->addColumn('exported_at', array(
            'header' => __('Export date'),
            'sortable' => true,
            'is_system' => true,
            'index' => 'exported_at',
            'filter' => false,
			'renderer' => 'Packetery\Checkout\Block\Adminhtml\Order\Renderer\ExportTime'
        ));

		$this->addExportType($this->getUrl('*/*/exportPacketeryCsv'), __('CSV - only not exported'));
		$this->addExportType($this->getUrl('*/*/exportPacketeryCsvAll'), __('CSV - all records'));

        return $this;
    }


	/**
	 * Option values Yes/No (Ano/Ne)
	 */
	public static function getOptionArray()
	{
		return array(__('No'), __('Yes'));
	}

    public function getOrderStatues() {
        return $collection = $this->statusCollectionFactory->toOptionHash();
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
	 * Custom filtration for COD parameter in order
	 * (like Packetery rules)
	 * @param $collection
	 * @param $column
	 */
	public function filterOptionCod($collection, $column)
	{
		$filterValue = intval($column->getFilter()->getValue());

		if($filterValue === 0)
		{
			$collection->getSelect()->where("cod = '0.00'");
		}

		if($filterValue === 1)
		{
			$collection->getSelect()->where("cod > 0");
		}
	}
    //TODO: Refactor - merge all filters into one method
	public function filterOptionRecipientName($collection, $column)
    {
        $filterValue = $column->getFilter()->getValue();
        if (!$filterValue)
        {
            return;
        }

        // remove spaces
        $filterValue = $this->removeSpaces($filterValue);

        $collection->getSelect()
            ->where('CONCAT(recipient_firstname, recipient_lastname) LIKE ?', "%{$filterValue}%");
    }

    public function filterPointName($collection, $column)
    {
        $filterValue = $column->getFilter()->getValue();
        if (!$filterValue)
        {
            return;
        }

        // remove spaces
        $filterValue = $this->removeSpaces($filterValue);
        $collection->getSelect()->where('CONCAT(point_name, point_id) LIKE ? ', "%{$filterValue}%");

    }

    public function filterRecipientAddress($collection, $column)
    {
        $filterValue = $column->getFilter()->getValue();
        if (!$filterValue)
        {
            return;
        }

        // remove spaces
        $filterValue = $this->removeSpaces($filterValue);
        $collection->getSelect()->where('CONCAT_WS("",recipient_street, recipient_house_number, recipient_city, recipient_zip) LIKE ? ', "%{$filterValue}%");

    }

    public function filterOrderStatus($collection, $column)
    {
        $filterValue = $column->getFilter()->getValue();
        if (!$filterValue)
        {
            return;
        }

        $collection->getSelect()->join(array('so' => 'sales_order'), 'order_number = so.increment_id')->where('so.status = ?', $filterValue);

    }

    /**
     * @param string $filterValue
     *
     * @return string
     */
    private function removeSpaces($filterValue) {
        return (preg_replace('/\s+/', '', $filterValue));
    }
}
