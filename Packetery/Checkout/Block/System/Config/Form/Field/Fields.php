<?php
namespace Packetery\Checkout\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class Active
 *
 * @package VendorName\SysConfigTable\Block\System\Config\Form\Field
 */
class Fields extends AbstractFieldArray
{

    /**
     * @var bool
     */
    protected $_addAfter = true;

    /**
     * @var
     */
    protected $_addButtonLabel;

    /**
     * Construct
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare to render the columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('from', ['label' => __('Weight from'), 'class' => 'input-text validate-zero-or-greater']);
        $this->addColumn('to', ['label' => __('Weight to (includes)'), 'class' => 'input-text validate-zero-or-greater']);
        $this->addColumn('price', ['label' => __('Price'), 'class' => 'input-text validate-zero-or-greater']);
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add rule');
    }
}
