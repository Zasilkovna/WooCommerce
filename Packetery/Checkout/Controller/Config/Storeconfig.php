<?php

namespace Packetery\Checkout\Controller\Config;

use Packetery\Checkout\Model\Carrier\Packetery;

class Storeconfig extends \Magento\Framework\App\Action\Action
{
    const MODUL_IDENTITY = 'magento-%s-packetery-%s';

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * TODO: refactoring - nowhere used
     * @var Packetery\Checkout\Model\Carrier\Packetery
     */
    protected $packetery;

    /** @var string */
    protected $version;

    /** @var \Packetery\Checkout\Helper\Data */
    private $helperData;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Packetery\Checkout\Model\Carrier\Packetery $packetery,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Packetery\Checkout\Helper\Data $helperData
    )
    {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->packetery = $packetery;

        $this->setMagentoVersion($productMetadata);
        $this->helperData = $helperData;
    }

    private function setMagentoVersion($productMetadata)
    {
        // Magento 2.0.x
        if (defined('\Magento\Framework\AppInterface::VERSION'))
        {
            $this->version = \Magento\Framework\AppInterface::VERSION;

            return;
        }

        $this->version = $productMetadata->getVersion();
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try
        {

            $appIdentity = sprintf(self::MODUL_IDENTITY, $this->version, $this->helperData->getModuleVersion());

            $config = [];

            $config['apiKey'] = $this->scopeConfig->getValue(
                'widget/options/api_key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // get localeCode
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $store = $objectManager->get('Magento\Framework\Locale\Resolver');
            // get language from getLocale()
            $locale = strstr($store->getLocale(), "_", TRUE);

            $config['packetaOptions'] = [
                'webUrl'      => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK),
                'appIdentity' => $appIdentity,
                // 'country'    => NULL, // unreachable, gets from JS
                'language'    => $locale,
            ];

            $response = [
                'success' => TRUE,
                'value'   => json_encode($config),
            ];

        }
        catch (\Exception $e)
        {
            $response = [
                'success' => FALSE,
                'value'   => __('There was an error during request.'),
            ];

            $this->messageManager->addError($e->getMessage());
        }
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($response);
    }

}
