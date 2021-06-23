<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Pricingrule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Packetery\Checkout\Model\Misc\ComboPhrase;

class MultiDetail extends Action implements HttpGetActionInterface
{
    /** @var PageFactory */
    private $pageFactory;

    /** @var \Magento\Directory\Model\CountryFactory  */
    private $countryFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $rawFactory
     */
    public function __construct(
        Context $context,
        PageFactory $rawFactory,
        CountryFactory $countryFactory
    ) {
        $this->pageFactory = $rawFactory;
        $this->countryFactory = $countryFactory;

        parent::__construct($context);
    }

    /**
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Packetery_Checkout::pricingRules');
        $country = $this->countryFactory->create()->loadByCode($this->getRequest()->getParam('country'));
        $resultPage->getConfig()->getTitle()->prepend(new ComboPhrase([$country->getName(), ' - ', __('Pricing rules')]));

        return $resultPage;
    }
}
