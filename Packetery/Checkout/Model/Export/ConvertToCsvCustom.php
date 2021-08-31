<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Export;

use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

class ConvertToCsvCustom
{
    /**
     * @var int
     */
    protected $pageSize;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * ConvertToCsvCustom constructor.
     *
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param int $pageSize
     */
    public function __construct(
        Filter $filter,
        int $pageSize = 200
    ) {
        $this->filter = $filter;
        $this->pageSize = $pageSize;
    }

    /**
     * Returns item IDs.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getItemIds(): array {
        $component = $this->filter->getComponent();

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $component->getContext()->getDataProvider();

        $i = 1;
        $searchCriteria = $dataProvider->getSearchCriteria()
            ->setCurrentPage($i)
            ->setPageSize($this->pageSize);
        $totalCount = (int)$dataProvider->getSearchResult()->getTotalCount();

        $orderIds = [];
        while ($totalCount > 0) {
            $items = $dataProvider->getSearchResult()->getItems();
            foreach ($items as $item) {
                $orderIds[] = $item->getId();
            }
            $searchCriteria->setCurrentPage(++$i);
            $totalCount = $totalCount - $this->pageSize;
        }

        return $orderIds;
    }
}
