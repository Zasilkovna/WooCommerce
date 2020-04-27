<?php
namespace Packetery\Checkout\Controller\Adminhtml\Order;

class ExportMass extends \Magento\Backend\App\Action
{

    protected $_fileFactory;
    protected $_response;
    protected $_view;
    protected $directory;
    protected $converter;
    protected $resultPageFactory;
    protected $directory_list;

    /** @var \Packetery\Checkout\Helper\Data */
    private $data;

    /** @var \Magento\Backend\App\Action\Context */
    private $context;

    public function __construct(
        \Magento\Backend\App\Action\Context  $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Packetery\Checkout\Helper\Data $data
    ) {
        parent::__construct($context);

        $this->resultPageFactory  = $resultPageFactory;
        $this->data = $data;
        $this->context = $context;
    }
    public function execute()
    {
        $orderIds = $this->getRequest()->getParam('order_id');

        if (empty($orderIds))
        {
            $this->messageManager->addError(__('No orders to export.'));
            $this->_redirect($this->_redirect->getRefererUrl());

            return;
        }

        $content = $this->resultPageFactory->create()->getLayout()->createBlock('Packetery\Checkout\Block\Adminhtml\Order\GridExport')->getCsvMassFileContents($orderIds);

        if (!$content)
        {
            $this->messageManager->addError(__('Error! No export data found.'));
            $this->_redirect($this->_redirect->getRefererUrl());

            return;
        }

        $resources = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\App\ResourceConnection');

        $connection = $resources->getConnection();
        $now = new \DateTime();

        $connection->update('packetery_order', ['exported_at' => $now->format('Y-m-d H:i:s'), 'exported' => 1], ['id IN (?)' => $orderIds]);

        $this->_sendUploadResponse($this->data->getExportFileName(), $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
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
