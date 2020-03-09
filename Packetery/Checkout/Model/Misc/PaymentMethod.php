<?php

namespace Packetery\Checkout\Model\Misc;

class PaymentMethod implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Payment\Model\Config $paymentConfig */
        $paymentConfig = $objectManager->get('Magento\Payment\Model\Config');
        $activePaymentMethods = $paymentConfig->getActiveMethods();

        $_return = [];

        /**
         * @var string                                 $code
         * @var \Magento\Payment\Model\MethodInterface $_method
         */
        foreach ($activePaymentMethods as $code => $_method)
        {
            $_return[] = [
                'value' => $_method->getCode(),
                'label' => $_method->getTitle()
            ];
        }

        return $_return;
    }
}
