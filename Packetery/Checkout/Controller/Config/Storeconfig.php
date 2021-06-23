<?php

namespace Packetery\Checkout\Controller\Config;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Storeconfig implements HttpGetActionInterface
{
    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var string */
    protected $version;

    /** @var \Packetery\Checkout\Helper\Data */
    private $helperData;

    /** @var \Packetery\Checkout\Model\Carrier\Imp\Packetery\Config */
    private $packeteryConfig;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /**
     * Storeconfig constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Packetery\Checkout\Helper\Data $helperData
     * @param \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packetery
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Packetery\Checkout\Helper\Data $helperData,
        \Packetery\Checkout\Model\Carrier\Imp\Packetery\Carrier $packetery
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->packeteryConfig = $packetery->getPacketeryConfig();
        $this->helperData = $helperData;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $config = [];
            $config['apiKey'] = $this->packeteryConfig->getApiKey();
            $config['packetaOptions'] = [
                'webUrl' => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK),
                'appIdentity' => $this->helperData->getPacketeryAppIdentity(),
                'language' => $this->helperData->getShortLocale(),
            ];

            $response = [
                'success' => true,
                'value' => json_encode($config),
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'value' => __('There was an error during request.'),
            ];

            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultJsonFactory->create()->setData($response);
    }

}
