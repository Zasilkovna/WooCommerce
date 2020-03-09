<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Packetery\Checkout\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Ui\Component\MassAction\Filter;
use Packetery\Checkout\Model\Export\ConvertToCsvCustom;

/**
 * Class Render
 */
class GridToCsvCustom extends Action
{
    /**
     * @var ConvertToCsvCustom
     */
    protected $converter;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var Filter
     */
    private $filter;


    /**
     * @param Context $context
     * @param ConvertToCsvCustom $converter
     * @param FileFactory $fileFactory
     * @param Filter|null $filter
     */
    public function __construct(
        Context $context,
        ConvertToCsvCustom $converter,
        FileFactory $fileFactory,
        Filter $filter = null
    ) {
        parent::__construct($context);
        $this->converter = $converter;
        $this->fileFactory = $fileFactory;
        $this->filter = $filter ?: ObjectManager::getInstance()->get(Filter::class);
    }

    /**
     * Export data provider to CSV
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        return $this->fileFactory->create('export.csv', $this->converter->getCsvFile(), 'var');
    }

    /**
     * Checking if the user has access to requested component.
     *
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        if ($this->_request->getParam('namespace')) {
            try {
                $component = $this->filter->getComponent();
                $dataProviderConfig = $component->getContext()
                    ->getDataProvider()
                    ->getConfigData();
                if (isset($dataProviderConfig['aclResource'])) {
                    return $this->_authorization->isAllowed(
                        $dataProviderConfig['aclResource']
                    );
                }
            } catch (\Throwable $exception) {


                return false;
            }
        }

        return true;
    }
}
