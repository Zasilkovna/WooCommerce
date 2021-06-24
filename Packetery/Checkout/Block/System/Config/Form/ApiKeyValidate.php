<?php


namespace Packetery\Checkout\Block\System\Config\Form;


use Magento\Framework\Phrase;

class ApiKeyValidate extends \Magento\Framework\App\Config\Value
{
    const API_KEY_LENGTH = 16;

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

        parent::beforeSave();
    }
}
