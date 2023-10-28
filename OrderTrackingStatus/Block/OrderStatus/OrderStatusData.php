<?php

namespace RltSquare\OrderTrackingStatus\Block\OrderStatus;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use RltSquare\OrderTrackingStatus\Helper\GatheringOrderStatusOptions;
use RltSquare\OrderTrackingStatus\Helper\GatheringVidaXlOrderStatusOptions;

class OrderStatusData extends Template
{

    protected RequestInterface $request;

    protected Order $order;

    protected ManagerInterface $messageManager;

    protected Json $json;
    private ProductRepositoryInterface $productRepository;
    private LoggerInterface $logger;

    private ImageHelper $imageHelper;
    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;
    /**
     * @var GatheringOrderStatusOptions
     */
    private GatheringOrderStatusOptions $gatheringOrderStatusOptions;
    /**
     * @var GatheringVidaXlOrderStatusOptions
     */
    private GatheringVidaXlOrderStatusOptions $gatheringVidaXlOrderStatusOptions;

    /**
     * Status constructor.
     * @param RequestInterface $request
     * @param Order $order
     * @param ManagerInterface $messageManager
     * @param Json $json
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param ImageHelper $imageHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param GatheringOrderStatusOptions $gatheringOrderStatusOptions
     * @param GatheringVidaXlOrderStatusOptions $gatheringVidaXlOrderStatusOptions
     * @param array $data
     */
    public function __construct(
        RequestInterface                  $request,
        Order                             $order,
        ManagerInterface                  $messageManager,
        Json                              $json,
        Context                           $context,
        ProductRepositoryInterface        $productRepository,
        LoggerInterface                   $logger,
        ImageHelper                       $imageHelper,
        OrderRepositoryInterface          $orderRepository,
        GatheringOrderStatusOptions       $gatheringOrderStatusOptions,
        GatheringVidaXlOrderStatusOptions $gatheringVidaXlOrderStatusOptions,
        array                             $data = []
    )
    {
        $this->request = $request;
        $this->order = $order;
        $this->messageManager = $messageManager;
        $this->json = $json;
        parent::__construct($context, $data);
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->imageHelper = $imageHelper;
        $this->orderRepository = $orderRepository;
        $this->gatheringOrderStatusOptions = $gatheringOrderStatusOptions;
        $this->gatheringVidaXlOrderStatusOptions = $gatheringVidaXlOrderStatusOptions;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function orderTracking(): array
    {
        $incrementId = $this->getRequest()->getParam('orderId'); // Get order ID from the request
        $order = $this->order->loadByIncrementId($incrementId);
        $tracksCollection = $order->getTracksCollection();
        $orderData = [
            'isOrderIdValid' => false,
            'orderIncrementId' => '',
            'deliveryAddress' => '',
            'mobile' => '',
            'email' => '',
            'shippingMethod' => '',
            'tracking_info' => []
        ];

        if ($order->getId()) {
            $orderData['isOrderIdValid'] = true;
            $orderData['orderIncrementId'] = $order->getIncrementId();

            // Retrieve the shipping address
            $shippingAddress = $order->getShippingAddress();
            if ($shippingAddress) {
                $deliveryAddress = $shippingAddress->getName() . '<br>' .
                    implode('<br>', $shippingAddress->getStreet()) . '<br>' .
                    $shippingAddress->getPostcode() . ' ' . $shippingAddress->getCity() . '<br>' .
                    $shippingAddress->getCountryId();
                $orderData['deliveryAddress'] = $deliveryAddress;
            }

            // Retrieve the billing address
            $billingAddress = $order->getBillingAddress();
            if ($billingAddress) {
                $billingInfo = $billingAddress->getName() . '<br>' .
                    implode('<br>', $billingAddress->getStreet()) . '<br>' .
                    $billingAddress->getPostcode() . ' ' . $billingAddress->getCity() . '<br>' .
                    $billingAddress->getCountryId();
                $orderData['billingAddress'] = $billingInfo;
            }
            // Retrieve payment information
            $payment = $order->getPayment();
            if ($payment) {
                $paymentMethod = $payment->getMethodInstance()->getTitle();
                $orderData['paymentMethod'] = $paymentMethod;

                $paymentDetails = $payment->getMethodInstance()->getAdditionalInformation();
                if (isset($paymentDetails['cc_last4'])) {
                    $orderData['paymentDetails'] = '************' . $paymentDetails['cc_last4'];
                }
            }

            // Order overview
            $currencyCode = 'Kr'; // Set the currency code to Swedish Krona (kr)

            // Format and display the prices with "kr"
            $orderOverview = 'Subtotal: ' . $this->formatPrice($order->getSubtotal(), $currencyCode) . '<br>' .
                'Delivery Amount: ' . $this->formatPrice($order->getShippingAmount(), $currencyCode) . '<br>' .
                'Discount: ' . $this->formatPrice($order->getDiscountAmount(), $currencyCode) . '<br>' .
                'Discount Code: ' . $order->getCouponCode() . '<br>' .
                '<!-- Line break -->' . '<hr>' .
                'Grand Total: ' . $this->formatPrice($order->getGrandTotal(), $currencyCode);
            $orderData['orderOverview'] = $orderOverview;

            // Retrieve mobile and email
            $orderData['mobile'] = $order->getBillingAddress()->getTelephone();
            $orderData['email'] = $order->getCustomerEmail();

            // Separate products based on the "DropShipper" attribute value
            foreach ($order->getAllItems() as $item) {
                $product = $item->getProduct();
                $dropShipperAttribute = $product->getResource()->getAttribute('DropShipper');

                if ($dropShipperAttribute && $dropShipperAttribute->usesSource()) {
                    $dropShipperValue = $dropShipperAttribute->getSource()->getOptionText($product->getData('DropShipper'));

                    if ($dropShipperValue === 'BigBuy') {
                        $productAttributes = $this->getProductAttributes($product->getId());
                        $orderData['products']['BigBuy'][] = [
                            'item_id' => $item->getId(),
                            'product_name' => $product->getName(),
                            'product_image' => $this->getProductImageUrl($product->getId()), // Get product image URL
                            'product_qty' => $item->getQtyOrdered(),
                            'product_attributes' => $productAttributes, // Include size and color attributes
                        ];
                    } elseif ($dropShipperValue === 'VidaXl') {
                        $productAttributes = $this->getProductAttributes($product->getId());
                        $orderData['products']['VidaXl'][] = [
                            'item_id' => $item->getId(),
                            'product_name' => $product->getName(),
                            'product_image' => $this->getProductImageUrl($product->getId()), // Get product image URL
                            'product_qty' => $item->getQtyOrdered(),
                            'product_attributes' => $productAttributes,
                        ];
                    }
                }
            }
            // Get shipping method
//            $orderData['shippingMethod'] = $order->getShippingMethod();
            $carrierName = '';

            $orderData['shippingMethod'] = $order->getShippingDescription();
            if ($orderData['shippingMethod']) {
                $carrierParts = explode('-', $orderData['shippingMethod'], 2);
                if (count($carrierParts) > 1) {
                    $carrierName = trim($carrierParts[1]);
                } else {
                    $carrierName = trim($carrierParts[0]);
                }
            }

            $orderData['shippingMethod'] = $carrierName;

            $bigBuyOrderId = $this->getBigBuyOrderId($order->getId());
            $vidaXlOrderId = $order->getId();

            if ($bigBuyOrderId !== null) {
                $bigBuyStatus = $this->getOrderStatusFromBigBuy((int)$bigBuyOrderId);

                $orderStatusText = '';
                $statusColor = '';

                if ($bigBuyStatus === 'Behandler') {
                    $orderStatusText = 'Order received';
                    $statusColor = 'red';
                } elseif ($bigBuyStatus === 'Package sent') {
                    $orderStatusText = 'During treatment';
                    $statusColor = 'yellow';
                } else {
                    $orderStatusText = 'Order Placed';
                    $statusColor = 'green';
                }

                $orderData['tracking_info'][] = [
                    'status' => $orderStatusText,
                    'date' => strtotime($order->getCreatedAt()),
                    'location' => '',
                    'status_color' => $statusColor, // Pass the status color to the HTML
                ];
            } elseif (!empty($vidaXlOrderId)) {
                $vidaXlStatus = $this->getOrderStatusFromVidaXl((int)$vidaXlOrderId);

                $orderStatusText = '';
                $statusColor = '';

                if ($vidaXlStatus === 'Temporary') {
                    $orderStatusText = 'Order Received';
                    $statusColor = 'yellow'; // Change the color to yellow for "Temporary" status
                } elseif ($vidaXlStatus === 'Package sent') {
                    // Handle other status conditions here
                } else {
                    $orderStatusText = 'Order received';
                    // You can set a different color for "Order received" status if needed
                }

                $orderData['tracking_info'][] = [
                    'status' => $orderStatusText,
                    'date' => strtotime($order->getCreatedAt()),
                    'location' => '',
                    'status_color' => $statusColor, // Pass the status color to the HTML
                ];
            }
        }

        return $orderData;
    }


    /**
     * Get the order status from Big Buy.
     *
     * @param int $bigBuyOrderId
     * @return string|null
     */
    private function getOrderStatusFromBigBuy(int $bigBuyOrderId): ?string
    {
        try {
            // Get the Big Buy order status from the API response
            $data = $this->gatheringOrderStatusOptions->gatheringOrderData($bigBuyOrderId);
            if ($data) {
                return $data['status'];
            }
        } catch (GuzzleException $e) {
            $this->logger->notice('Order Status Option for BigBuy did not receive ' . $e->getMessage());
        } catch (LocalizedException $e) {
            $this->logger->notice('LocalizedException: ' . $e->getMessage());
        }

        return null;
    }


    private function getOrderStatusFromVidaXl($vidaXlOrderId): ?string
    {
        try {
            // Get the VidaXL order status from the API response
            $orderData = $this->gatheringVidaXlOrderStatusOptions->gatheringVidaXlOrderData($vidaXlOrderId);
            $innerArray = $orderData[0];
            return $innerArray['order']['status_order_name']; // Replace with the actual path to the status in the API response
        } catch (GuzzleException $e) {
            $this->logger->notice('Order Status Option for VidaXl did not receive ' . $e->getMessage());
        } catch (LocalizedException $e) {
            $this->logger->notice('LocalizedException: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param $orderId
     * @return float|mixed|null
     */
    private function getBigBuyOrderId($orderId): mixed
    {
        try {

            $order = $this->orderRepository->get((int)$orderId);

            if ($order && $order->getId()) {
                $bigBuyOrderId = $order->getData('big_buy_order_id');
                // Check if the "big_buy_order_id" attribute exists and is not empty
                if ($bigBuyOrderId !== null && $bigBuyOrderId !== '') {
                    return $bigBuyOrderId;
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }


    /**
     * @param int $productId
     * @return string|null
     */
    public function getProductImageUrl(int $productId): ?string
    {
        $imageUrl = null;
        try {
            $product = $this->productRepository->getById($productId);
            if ($product instanceof Product) {
                $image = $this->imageHelper->init($product, 'product_page_image_small');
                $imageUrl = $image->getUrl();
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
        return $imageUrl;
    }

    /**
     * @param int $productId
     * @return array|null
     */
    private function getProductAttributes(int $productId): ?array
    {
        try {
            $product = $this->productRepository->getById($productId);
            if ($product) {
                $attributes = [];

                // Check if the product has the "size" attribute
                if ($product->getData('size')) {
                    $attributes['size'] = $product->getAttributeText('size');
                }

                // Check if the product has the "color" attribute
                if ($product->getData('color')) {
                    $attributes['color'] = $product->getAttributeText('color');
                }

                return $attributes;
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    /**
     * Format the price with the specified currency code
     *
     * @param float $price
     * @param string $currencyCode
     * @return string
     */
    private function formatPrice(float $price, string $currencyCode): string
    {
        return number_format($price, 2) . ' ' . $currencyCode;
    }

}

