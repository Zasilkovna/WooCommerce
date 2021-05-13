<?php

namespace Packetery\Checkout\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Packetery\Checkout\Model\Pricing;

class Packetery extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    const MODUL_TITLE = 'title';
    const MODUL_METHOD_NAME = 'name';
    const MULTI_SHIPPING_MODULE_NAME = 'multishipping';

    /** @var string  */
    protected $_code = 'packetery';

    /** @var bool  */
    protected $_isFixed = true;

    /** @var \Magento\Framework\App\Request\Http */
    private $httpRequest;

    /** @var \Packetery\Checkout\Model\Pricing\Service */
    private $pricingService;

    /** @var \Packetery\Checkout\Model\Carrier\PacketeryConfig */
    private $packeteryConfig;

    /**
     * Packetery constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Request\Http $httpRequest,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->httpRequest = $httpRequest;
        $this->pricingService = $pricingService;
        $this->packeteryConfig = new PacketeryConfig($this);
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if ($this->httpRequest->getModuleName() == self::MULTI_SHIPPING_MODULE_NAME) {
            return false;
        }

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $pricingRequest = new Pricing\Request($request, $this->packeteryConfig, $this->_code);
        $result = $this->pricingService->collectRates($pricingRequest);
        if (!$result instanceof \Magento\Shipping\Model\Rate\Result) {
            return false;
        }

        return $result;
    }

    /**
     * getAllowedMethods
     *
     * @param array
     */
    public function getAllowedMethods(): array
    {
        return ['flatrate' => $this->getConfigData('name')];
    }
}
