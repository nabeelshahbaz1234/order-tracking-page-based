<?php
declare(strict_types=1);

namespace RltSquare\OrderTrackingStatus\Controller\OrderStatus;

use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * @class Save
 */
class Save implements ActionInterface
{

    protected RequestInterface $requestInterface;
    protected Redirect $resultRedirectFactory;
    private ResultFactory $resultFactory;
    private ManagerInterface $messageManager;
    private OrderRepositoryInterface $orderRepository;
    private Order $order;


    /**
     * @param RequestInterface $requestInterface
     * @param Redirect $resultRedirectFactory
     * @param ResultFactory $resultFactory
     * @param ManagerInterface $messageManager
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        RequestInterface         $requestInterface,
        Redirect                 $resultRedirectFactory,
        ResultFactory            $resultFactory,
        ManagerInterface         $messageManager,
        OrderRepositoryInterface $orderRepository,
        Order                    $order
    )
    {
        $this->requestInterface = $requestInterface;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resultFactory = $resultFactory;
        $this->orderRepository = $orderRepository;
        $this->order = $order;
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        /** @var  $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        /** @var $incrementId */
        $incrementId = $this->requestInterface->getParam('order_id');
        $email = $this->requestInterface->getParam('email');

        if (!$incrementId || !$email) {
            $this->messageManager->addErrorMessage(__('Order ID or Email is missing.'));
            return $resultRedirect->setPath('track/orderstatus/status'); // Redirect back to the form page.
        }

        try {
            $order = $this->order->loadByIncrementId($incrementId);

            // Check if the order contains the provided email
            $customerEmail = $order->getCustomerEmail();
            if ($customerEmail !== $email) {
                $this->messageManager->addErrorMessage(__('Email does not match the order.'));
                return $resultRedirect->setPath('track/orderstatus/status'); // Redirect back to the form page.
            }

        } catch (NoSuchEntityException $e) {
            // If the order is not found, display an error message and handle it accordingly
            $this->messageManager->addErrorMessage(__('Order not found: %1', $e->getMessage()));
            return $resultRedirect->setPath('track/orderstatus/status'); // Redirect back to the form page.
        }

        /** @var Redirect $resultRedirect */
        return $resultRedirect->setPath('custom/index/index', ['orderId' => $incrementId]);
    }


}
