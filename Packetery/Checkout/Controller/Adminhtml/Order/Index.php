<?php

namespace Packetery\Checkout\Controller\Adminhtml\Order;

class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory = false;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Packeta - orders'));



        $resultPage->addContent(
            $resultPage->getLayout()->createBlock('Packetery\Checkout\Block\Adminhtml\Order')
        );



        return $resultPage;
    }
}
