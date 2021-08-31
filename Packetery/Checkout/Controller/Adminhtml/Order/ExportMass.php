<?php

declare(strict_types=1);

namespace Packetery\Checkout\Controller\Adminhtml\Order;

use Packetery\Checkout\Model\Export\ConvertToCsvCustom;

class ExportMass extends \Magento\Backend\App\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    private $resultPageFactory;

    /** @var \Packetery\Checkout\Helper\Data */
    private $data;

    /** @var \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    /** @var ConvertToCsvCustom */
    private $converter;

    /**
     * ExportMass constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Packetery\Checkout\Helper\Data $data
     * @param \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Packetery\Checkout\Model\Export\ConvertToCsvCustom $converter
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Packetery\Checkout\Helper\Data $data,
        \Packetery\Checkout\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        ConvertToCsvCustom $converter
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->data = $data;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->converter = $converter;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute() {
        $selected = $this->getRequest()->getParam('selected');
        if ($selected === 'false') {
            $this->messageManager->addError(__('No orders to export.'));
            $this->_redirect($this->_redirect->getRefererUrl());
            return;
        }

        $orderIds = $this->converter->getItemIds();

        if (empty($orderIds)) {
            $this->messageManager->addError(__('No orders to export.'));
            $this->_redirect($this->_redirect->getRefererUrl());
            return;
        }

        $content = $this->resultPageFactory->create()->getLayout()->createBlock('Packetery\Checkout\Block\Adminhtml\Order\GridExport')->getCsvMassFileContents($orderIds);

        if (!$content) {
            $this->messageManager->addError(__('Error! No export data found.'));
            $this->_redirect($this->_redirect->getRefererUrl());
            return;
        }

        $now = new \DateTime();

        /** @var \Packetery\Checkout\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('id', ['in' => $orderIds]);
        $collection->setDataToAll(
            [
                'exported_at' => $now->format('Y-m-d H:i:s'),
                'exported' => 1,
            ]
        );
        $collection->save();

        $this->_sendUploadResponse($this->data->getExportFileName(), $content);
    }

    /**
     * @param string $fileName
     * @param string|null $content
     * @param string $contentType
     */
    protected function _sendUploadResponse(string $fileName, ?string $content, string $contentType = 'application/octet-stream') {
        $this->_response->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', strlen($content), true)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"', true)
            ->setHeader('Last-Modified', date('r'), true)
            ->setBody($content)
            ->sendResponse();
        die;
    }
}
