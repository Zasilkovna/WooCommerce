<?php

namespace Packetery\Checkout\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;

class Packetery extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    const MODUL_TITLE = 'title';
    const MODUL_METHOD_NAME = 'name';
    const MODUL_CONF = 'packetery_rules/%s/';
    const MODUL_CONF_GLOBAL = 'packetery_rules/global/';
    const MODUL_CONF_MAX_WEIGHT = 'packetery_rules/rules_global/max_weight';

    const MULTI_SHIPPING_MODULE_NAME = 'multishipping';

    protected $_code = 'packetery';

    protected $_isFixed = true;

    protected $_scopeConfig;

    protected $_rateResultFactory;

    protected $_rateMethodFactory;

    protected $_countryCode;

    protected $_weightTotal;

    protected $_weightRules;

    protected $_priceTotal;

    protected $_configPath;

    protected $_globalConfigPath;

    /** @var \Magento\Framework\App\Request\Http */
    private $httpRequest;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\App\Request\Http $httpRequest,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->httpRequest = $httpRequest;
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if ($this->httpRequest->getModuleName() == self::MULTI_SHIPPING_MODULE_NAME)
        {
            return FALSE;
        }

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $this->initProps($request);

        // not allowed country, Packetery shipment is not displayed
        if (!self::canUsePacketa($this->_scopeConfig, $this->_countryCode))
        {
           return FALSE;
        }

        $_weightMax = $this->_scopeConfig->getValue(self::MODUL_CONF_MAX_WEIGHT, \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $_freeShipping = $this->getFreeShipping();

        // Package is over maximum allowed weight
        if (!empty($_weightMax) && $this->_weightTotal > $_weightMax)
        {
            return false;
        }

        $result = $this->_rateResultFactory->create();

        // Free Shipping is enabled && price is over free shipping threshold
        if ($_freeShipping !== FALSE && $_freeShipping <= $this->_priceTotal)
        {
            $result->append($this->_getFree());
        }
        // if weight rules are not set or are empty
        elseif (!$this->_weightRules || count($this->_weightRules) < 1)
        {
            $result->append($this->_getDefault());
        }
        else
        {
            $result->append($this->_getWeighted());
        }

        return $result;
    }

    protected function _getDefault()
    {
        // save price and cost
        $defaultPrice = $this->getStoreConfig("{$this->_configPath}default_price");
        $globalDefaultPrice = $this->getStoreConfig("{$this->_globalConfigPath}default_price");

        if (!$defaultPrice)
        {
            $defaultPrice = $globalDefaultPrice;
        }
        if (!$defaultPrice)
        {
            $defaultPrice = 0;
        }

        return $this->initMethodPrice($defaultPrice);
    }

    protected function _getFree()
    {
        return $this->initMethodPrice(0);
    }

    protected function _getWeighted()
    {
        $price = PHP_INT_MIN;

        foreach ($this->_weightRules as $rule)
        {
            // "Weight from" is included
            if ($this->_weightTotal > $rule['from'] && $this->_weightTotal <= $rule['to'])
            {
                $price = $rule['price'];
            }
        }

        // No rules apply use default price
        if ($price < 0)
        {
            return $this->_getDefault();
        }

        return $this->initMethodPrice($price);
    }

    private function initProps(RateRequest $request)
    {
        $this->_countryCode = strtolower($request->getDestCountryId());
        $this->_weightTotal = $request->getPackageWeight();
        $this->_priceTotal = $request->getPackageValue();
        $this->_globalConfigPath = self::MODUL_CONF_GLOBAL;

        $this->_configPath = sprintf(self::MODUL_CONF, "rules_{$this->_countryCode}");

        if (!$this->getStoreConfig("{$this->_configPath}rules"))
        {
            $this->_configPath = sprintf(self::MODUL_CONF, '');
        }

        $config = $this->getStoreConfig("{$this->_configPath}rules");

        // Weight rules are set
        $this->_weightRules = json_decode($config, TRUE);
    }

    /**
     * Settings for free shipping
     * @return int | bool
     */
    private function getFreeShipping()
    {
        $_countryFreeShipping = $this->getStoreConfig("{$this->_configPath}free_shipping");
        $_globalFreeShipping = $this->getStoreConfig("{$this->_globalConfigPath}free_shipping");

        // Use country specific free shipping
        if (!empty($_countryFreeShipping))
        {
            $_freeShipping = $_countryFreeShipping;
        }
        // Use global free shipping
        elseif (!empty($_globalFreeShipping))
        {
            $_freeShipping = $_globalFreeShipping;
        }
        // Free shipping is disabled
        else
        {
            $_freeShipping = FALSE;
        }

        return $_freeShipping;
    }

    private function initMethodPrice($price)
    {
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier($this->_code);

        if (empty($method->getCarrierTitle()))
        {
            $moduleTitle = ($this->getConfigData(self::MODUL_TITLE) ? $this->getConfigData(self::MODUL_TITLE) : __("Packeta"));
            $method->setCarrierTitle($moduleTitle);
        }

        // save method information
        $method->setMethod($this->_code);

        if (empty($method->getMethodTitle()))
        {
            $methodTitle = ($this->getConfigData(self::MODUL_METHOD_NAME) ? $this->getConfigData(self::MODUL_METHOD_NAME) : __("Z-Point"));
            $method->setMethodTitle($methodTitle);
        }

        // save price and cost
        $method->setCost($price);
        $method->setPrice($price);

        return $method;
    }

    private function getStoreConfig($configPath)
    {
        return $this->_scopeConfig->getValue("{$configPath}", \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
    }

    /**
     * getAllowedMethods
     *
     * @param array
     */
    public function getAllowedMethods()
    {
        return ['flatrate' => $this->getConfigData('name')];
    }

    public static function getCountryCodes(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        // get settings for countries
        $configCountries = $scopeConfig->getValue(
            'packetery_rules',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // get country codes
        $countryCodes = [];
        foreach ($configCountries as $rulesKey => $rules)
        {
            if (in_array($rulesKey, ['rules_global', 'rules_default']))
            {
                continue;
            }

            $countryCodes[] = explode('_', $rulesKey)[1];
        }

        return $countryCodes;
    }

    /**
     * Is Packetery available for entered country?
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param string                                             $countryCode
     *
     * @return bool
     */
    public static function canUsePacketa(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, $countryCode)
    {
        if (!is_string($countryCode))
        {
            return FALSE;
        }

        return in_array(strtolower($countryCode), self::getCountryCodes($scopeConfig));
    }
}
