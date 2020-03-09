<?php


namespace Packetery\Checkout\Block\System\Config\Form;


use Magento\Framework\Phrase;

class ApiKeyValidate extends \Magento\Framework\App\Config\Value
{
    const API_KEY_LENGTH = 16;

    /** @var \Magento\Framework\Filesystem\DriverInterface */
    private $driver;

    public function __construct(
        \Magento\Framework\Filesystem\Driver\Http $driver,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = NULL,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = NULL,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->driver = $driver;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function beforeSave() {

        $apiKey = $this->getValue();

        if(strlen($apiKey) !== self::API_KEY_LENGTH) {

            $message = _(sprintf("The API key length must have %d characters!", self::API_KEY_LENGTH));
            throw new \Magento\Framework\Exception\ValidatorException(new Phrase($message));
        }
        $result = $this->driver->fileGetContents(sprintf("www.zasilkovna.cz/api/test?key=%s", $apiKey));
        if($result !== "1") {
            $message = _("The specified API key is not valid!");
            throw new \Magento\Framework\Exception\ValidatorException(new Phrase($message));
        }
        parent::beforeSave();
    }
}
