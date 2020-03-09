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

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->moduleList = $moduleList;
    }

    /**
     * @param string $extension
     *
     * @return string
     */
    public function getExportFileName($extension = 'csv') {

        $dateTime = $this->timezone->date()->format('Y-m-d-His');

        return  sprintf('%s-%s.%s', self::EXPORT_FILE_NAME, $dateTime, $extension);
    }

    /**
     * @return string
     */
    public function getModuleVersion() {

        return $this->moduleList->getOne($this->_getModuleName())['setup_version'];
    }

}
