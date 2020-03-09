<?php
namespace Packetery\Checkout\Block\Adminhtml;

class Order extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_order';
        $this->_blockGroup = 'Packetery_Checkout';
        $this->_headerText = __('Orders');
        parent::_construct();

        $this->removeButton('add');
    }
}
