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
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getDataItem(array $data, string $key, $default) {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        return ($data[$key] ?: $default);
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
        $misc = $postData['misc'];

        $collection = $this->orderCollectionFactory->create();
        $collection->addFilter('id', $id);

        if ($misc['isAnyAddressDelivery'] === '1') {
            $collection->setDataToAll(
                [
                    'address_validated' => $postData['address_validated'],
                    'recipient_street' => $this->getDataItem($postData, 'recipient_street', null),
                    'recipient_house_number' => $this->getDataItem($postData, 'recipient_house_number', null),
                    'recipient_country_id' => $this->getDataItem($postData, 'recipient_country_id', null),
                    'recipient_county' => $this->getDataItem($postData, 'recipient_county', null),
                    'recipient_city' => $this->getDataItem($postData, 'recipient_city', null),
                    'recipient_zip' => $this->getDataItem($postData, 'recipient_zip', null),
                    'recipient_longitude' => $this->getDataItem($postData, 'recipient_longitude', null),
                    'recipient_latitude' => $this->getDataItem($postData, 'recipient_latitude', null),
                ]
            );
        }

        if ($misc['isPickupPointDelivery'] === '1') {
            $collection->setDataToAll(
                [
                    'point_id' => $postData['point_id'],
                    'point_name' => $postData['point_name'],
                    'is_carrier' => (bool)$postData['is_carrier'],
                    'carrier_pickup_point' => $this->getDataItem($postData, 'carrier_pickup_point', null),
                ]
            );
        }

        $collection->save();

        $this->messageManager->addSuccessMessage(
            __('Saved')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/order/detail/id/' . $id);
    }
}
