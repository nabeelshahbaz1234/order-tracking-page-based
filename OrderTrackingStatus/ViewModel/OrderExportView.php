<?php
declare(strict_types=1);


namespace RltSquare\OrderTrackingStatus\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;


class OrderExportView implements ArgumentInterface
{

    /** @var null|OrderInterface */
    protected ?OrderInterface $order = null;
    /** @var RequestInterface */
    protected RequestInterface $request;
    /** @var OrderRepositoryInterface */
    protected OrderRepositoryInterface $orderRepository;

    /** @var UrlInterface */
    protected UrlInterface $urlBuilder;

    public function __construct(
        RequestInterface         $request,
        OrderRepositoryInterface $orderRepository,
        UrlInterface             $urlBuilder,

    )
    {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->urlBuilder = $urlBuilder;
    }


    public function getOrder(): ?OrderInterface
    {
        if ($this->order === null) {
            $orderId = (int)$this->request->getParam('order_id');
            if (!$orderId) {
                return null;
            }

            try {
                $order = $this->orderRepository->get($orderId);
            } catch (NoSuchEntityException $e) {
                return null;
            }
            $this->order = $order;
        }

        return $this->order;
    }

    /**
     * @return string
     */
    public function getSaveUrl(): string
    {
        return $this->urlBuilder->getUrl('order_edit/submit/save');
    }

}
