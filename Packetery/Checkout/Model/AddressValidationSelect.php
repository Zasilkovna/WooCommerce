<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model;

use Magento\Framework\Data\OptionSourceInterface;

class AddressValidationSelect implements OptionSourceInterface
{
    public const NONE = 'none';
    public const OPTIONAL = 'optional';
    public const REQUIRED = 'required';

    public function toOptionArray() {
        return [
            ['value' => self::NONE, 'label' => __('None address validation')],
            ['value' => self::OPTIONAL, 'label' => __('Optional address validation')],
            ['value' => self::REQUIRED, 'label' => __('Required address validation')],
        ];
    }
}
