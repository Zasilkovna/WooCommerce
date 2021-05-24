<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Context $context,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;

        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        $postData = $this->getRequest()->getPostValue()['general'];
        $id = $postData['id'];
        $carrierPickupPoint = ($postData['carrier_pickup_point'] ?? null);

        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('id', $id);
        $collection->setDataToAll(
            [
                'point_id' => $postData['point_id'],
                'point_name' => $postData['point_name'],
                'is_carrier' => (bool)$postData['is_carrier'],
                'carrier_pickup_point' => ($carrierPickupPoint ?: null),
            ]
        );
        $collection->save();

        $this->messageManager->addSuccessMessage(
            __('Saved')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/order/detail/id/' . $id);
    }
}
