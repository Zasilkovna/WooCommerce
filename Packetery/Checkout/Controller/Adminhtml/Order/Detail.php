<?php

namespace Packetery\Checkout\Controller\Adminhtml\Order;

use Magento\Framework\Controller\AbstractResult;
use Packetery\Checkout\Model\Carrier\Methods;

class Detail extends \Magento\Backend\App\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /**
     * Detail constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollection
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->orderFactory = $orderFactory;
        $this->orderCollectionFactory = $orderCollection;
    }

    /**
     * @return \Magento\Framework\Controller\AbstractResult
     */
    public function execute(): AbstractResult
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::orders');
        $resultPage->getConfig()->getTitle()->prepend(__('Orders'));
        $resultPage->getConfig()->getTitle()->prepend(__('Detail'));

        $id = $this->getRequest()->getParam('id');
        $orderCollection = $this->orderCollectionFactory->create();
        $order = $orderCollection->getItemById($id);

        if (empty($order)) {
            $this->messageManager->addErrorMessage(__('Page not found'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $magentoOrder = $this->orderFactory->create()->loadByIncrementId($order->getData('order_number'));
        $shippingMethod = $magentoOrder->getShippingMethod(true);

        if (!$shippingMethod || ($shippingMethod->getData('method') !== Methods::PICKUP_POINT_DELIVERY && $shippingMethod->getData('method') !== 'packetery')) {
            $this->messageManager->addErrorMessage(__('Page not found'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        return $resultPage;
    }
}
