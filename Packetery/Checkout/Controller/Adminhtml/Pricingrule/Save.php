<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Pricingrule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Packetery\Checkout\Model\Pricing;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Packetery_Checkout::packetery';

    /** @var \Packetery\Checkout\Model\PricingruleFactory */
    private $pricingruleFactory;

    /** @var \Packetery\Checkout\Model\WeightruleFactory */
    private $weightruleFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Pricingrule\Collection */
    private $pricingRuleCollectionFactory;

    /** @var \Packetery\Checkout\Model\ResourceModel\Weightrule\Collection */
    private $weightRuleCollectionFactory;

    /** @var Pricing\Service */
    private $pricingService;

    /** @var \Packetery\Checkout\Model\ResourceModel\PricingruleRepository */
    private $pricingruleRepository;

    /** @var \Packetery\Checkout\Model\Carrier\PacketeryConfig */
    private $packeteryConfig;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Packetery\Checkout\Model\PricingruleFactory $pricingruleFactory
     * @param \Packetery\Checkout\Model\WeightruleFactory $weightruleFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory
     * @param \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory
     * @param \Packetery\Checkout\Model\Pricing\Service $pricingService
     * @param \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository
     * @param \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig
     */
    public function __construct(
        Context $context,
        \Packetery\Checkout\Model\PricingruleFactory $pricingruleFactory,
        \Packetery\Checkout\Model\WeightruleFactory $weightruleFactory,
        \Packetery\Checkout\Model\ResourceModel\Pricingrule\CollectionFactory $pricingRuleCollectionFactory,
        \Packetery\Checkout\Model\ResourceModel\Weightrule\CollectionFactory $weightRuleCollectionFactory,
        \Packetery\Checkout\Model\Pricing\Service $pricingService,
        \Packetery\Checkout\Model\ResourceModel\PricingruleRepository $pricingruleRepository,
        \Packetery\Checkout\Model\Carrier\PacketeryConfig $packeteryConfig
    ) {
        $this->pricingruleFactory = $pricingruleFactory;
        $this->pricingRuleCollectionFactory = $pricingRuleCollectionFactory;
        $this->weightruleFactory = $weightruleFactory;
        $this->weightRuleCollectionFactory = $weightRuleCollectionFactory;
        $this->pricingService = $pricingService;
        $this->pricingruleRepository = $pricingruleRepository;
        $this->packeteryConfig = $packeteryConfig;

        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        $postData = $this->getRequest()->getPostValue()['general'];

        $weightRules = $postData['weightRules']['weightRules'] ?? [];
        unset($postData['weightRules']);

        if (empty($postData['free_shipment']) && !is_numeric($postData['free_shipment'])) {
            $postData['free_shipment'] = null; // empty string is casted to 0
        }

        try {
            $item = $this->pricingruleRepository->savePricingRule($postData, $weightRules);
        } catch (\Packetery\Checkout\Model\Exception\DuplicateCountryValidationException $e) {
            $this->messageManager->addErrorMessage(__('Price rule for specified country already exists'));
            return $this->createPricingRuleDetailRedirect(isset($postData['id']) ? $postData['id'] : null);
        } catch (\Packetery\Checkout\Model\Exception\MaxWeightValidationException $e) {
            $this->messageManager->addErrorMessage(__('The weight is invalid', $this->packeteryConfig->getMaxWeight()));
            return $this->createPricingRuleDetailRedirect(isset($postData['id']) ? $postData['id'] : null);
        } catch (\Packetery\Checkout\Model\Exception\PricingRuleNotFound $e) {
            $this->messageManager->addErrorMessage(__('Pricing rule not found'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/pricingrule/items');
        } catch (\Packetery\Checkout\Model\Exception\WeightRuleMissing $e) {
            $this->messageManager->addErrorMessage(__('Weight rule is missing'));
            return $this->createPricingRuleDetailRedirect(isset($postData['id']) ? $postData['id'] : null);
        }

        $this->messageManager->addSuccessMessage(
            __('Saved')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/pricingrule/detail/id/' . $item->getId());
    }

    /**
     * @param $id
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function createPricingRuleDetailRedirect($id): Redirect
    {
        if ($id > 0) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/pricingrule/detail/id/' . $id);
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('packetery/pricingrule/detail');
    }
}
