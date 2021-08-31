<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Packetery\Checkout\Ui\Component\Order\Listing\ByFieldColumnTrait;

class YesNo extends Column
{
    use ByFieldColumnTrait;

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $value = $item[$this->getByField()];

                if (is_numeric($value)) {
                    $value = (float)$value;
                }

                $item[$this->getData('name')] = (!empty($value) ? __('Yes') : __('No'));
            }
        }

        return $dataSource;
    }
}
