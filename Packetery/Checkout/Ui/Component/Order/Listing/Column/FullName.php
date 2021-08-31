<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class FullName extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $firstname = ($item['recipient_firstname'] ?: false);
                $lastname = ($item['recipient_lastname'] ?: false);
                $item[$this->getData('name')] = implode(' ', array_filter([$firstname, $lastname]));
            }
        }

        return $dataSource;
    }
}
