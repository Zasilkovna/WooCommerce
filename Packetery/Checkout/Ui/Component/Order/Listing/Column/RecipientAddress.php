<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component\Order\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class RecipientAddress extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = implode(
                    ', ',
                    array_filter(
                        [
                            implode(
                                ' ',
                                array_filter(
                                    [
                                        $item['recipient_street'],
                                        $item['recipient_house_number'],
                                    ]
                                )
                            ),
                            $item['recipient_city'],
                            $item['recipient_zip'],
                        ]
                    )
                );
            }
        }

        return $dataSource;
    }
}
