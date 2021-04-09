<?php


namespace Packetery\Checkout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const EXPORT_FILE_NAME = 'packetExport';

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /** @var \Magento\Framework\Module\ModuleListInterface */
    private $moduleList;

    /** @var \Magento\Framework\Locale\Resolver */
    private $localeResolver;

    /** @var \Magento\Framework\App\ProductMetadataInterface */
    private $productMetadata;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Locale\Resolver $localeResolver
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->moduleList = $moduleList;
        $this->localeResolver = $localeResolver;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param string $extension
     *
     * @return string
     */
    public function getExportFileName($extension = 'csv'): string {
        $dateTime = $this->timezone->date()->format('Y-m-d-His');
        return  sprintf('%s-%s.%s', self::EXPORT_FILE_NAME, $dateTime, $extension);
    }

    /**
     * @return string
     */
    public function getModuleVersion(): string {

        return $this->moduleList->getOne($this->_getModuleName())['setup_version'];
    }

    /**
     * @return string|null
     */
    public function getShortLocale(): ?string
    {
        return strstr($this->localeResolver->getLocale(), "_", true) ?: null;
    }

    /**
     * @return string|mixed
     */
    public function getMagentoVersion()
    {
        // Magento 2.0.x
        if (defined('\Magento\Framework\AppInterface::VERSION')) {
            return \Magento\Framework\AppInterface::VERSION;
        }

        return $this->productMetadata->getVersion();
    }

    /**
     * @return string
     */
    public function getPacketeryAppIdentity(): string
    {
        return sprintf('magento-%s-packetery-%s', $this->getMagentoVersion(), $this->getModuleVersion());
    }
}
