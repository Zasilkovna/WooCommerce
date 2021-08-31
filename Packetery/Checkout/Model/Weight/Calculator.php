<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Weight;

class Calculator
{
    /**
     * Returns total weight of ordered items.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return float
     */
    public function getOrderWeight(\Magento\Sales\Model\Order $order): float {
        /** @var \Magento\Sales\Model\Order\Item[] $allVisibleItems */
        $allVisibleItems = $order->getAllVisibleItems();
        $allVisibleItems = \Packetery\Checkout\Model\Weight\Item::transformItems($allVisibleItems);
        return $this->getItemsWeight($allVisibleItems);
    }

    /**
     * @param Item[] $allVisibleItems
     * @return float
     */
    public function getItemsWeight(array $allVisibleItems): float {
        $productWeights = [];
        $totalWeight = 0.0;

        foreach ($allVisibleItems as $item) {
            if ($item->getProductType() === 'configurable') {
                /** @var \Magento\Catalog\Model\Product $configurableProduct */
                $configurableProduct = $item->getProduct();
                if ($configurableProduct->isVirtual()) {
                    $configurableWeight = 0.0;
                } else {
                    $configurableWeight = $configurableProduct->getWeight();
                }

                /** @var Item[] $children */
                $children = ($item->getChildren() ?: []); // contains only ordered items
                foreach ($children as $child) {
                    /** @var \Magento\Catalog\Model\Product $childProduct */
                    $childProduct = $child->getProduct();
                    if ($childProduct->isVirtual()) {
                        $productWeights[$childProduct->getId()] = 0.0;
                        continue;
                    }

                    if (is_numeric($childProduct->getWeight())) {
                        $childWeight = $childProduct->getWeight();
                    } else {
                        $childWeight = $configurableWeight;
                    }

                    $productWeights[$childProduct->getId()] = $childWeight * $child->getQty();
                }
            }
        }

        foreach ($allVisibleItems as $item) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $item->getProduct();
            if ($item->getProductType() === 'simple' && !array_key_exists($product->getId(), $productWeights)) {
                if ($product->isVirtual()) {
                    $productWeights[$product->getId()] = 0.0;
                    continue;
                }

                $productWeights[$product->getId()] = $product->getWeight() * $item->getQty();
            }
        }

        foreach ($productWeights as $itemWeight) {
            $totalWeight += (float)$itemWeight;
        }

        return $totalWeight;
    }
}
