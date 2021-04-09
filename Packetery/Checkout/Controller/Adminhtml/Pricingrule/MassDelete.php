<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Pricingrule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Ui\Component\MassAction\Filter;
use Packetery\Checkout\Model\ResourceModel\Pricingrule;
use \Packetery\Checkout\Model\ResourceModel\Weightrule;

class MassDelete extends Action implements HttpPostActionInterface
{
    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory */
    private $pricingRuleCollectionFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory */
    private $weightRulesCollectionFactory;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $collectionFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Pricingrule\CollectionFactory $collectionFactory,
        Weightrule\CollectionFactory $weightRuleCollectionFactory
    ) {
        $this->filter = $filter;
        $this->pricingRuleCollectionFactory = $collectionFactory;
        $this->weightRulesCollectionFactory = $weightRuleCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Category delete action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\Collection $collection */
        $collection = $this->filter->getCollection($this->pricingRuleCollectionFactory->create());
        $deleted = 0;

        foreach ($collection->getItems() as $pricingRule) {
            $weightRulesCollection = $this->weightRulesCollectionFactory->create();
            $weightRulesCollection->addFilter('packetery_pricing_rule_id', $pricingRule->getId());

            foreach ($weightRulesCollection->getItems() as $weightRulesItem) {
                $weightRulesItem->delete();
            }

            $pricingRule->delete();
            $deleted++;
        }

        if ($deleted) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $deleted)
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/pricingrule/items');
    }
}
