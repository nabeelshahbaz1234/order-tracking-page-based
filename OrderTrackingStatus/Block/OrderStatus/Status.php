<?php

namespace RltSquare\OrderTrackingStatus\Block\OrderStatus;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;

class Status extends Template

{

    private UrlInterface $urlBuilder;

    public function __construct(
        UrlInterface $urlBuilder,
        Context      $context,
        array        $data = []
    )

    {
        parent::__construct($context, $data);

        $this->urlBuilder = $urlBuilder;
    }

    public function getFormActionUrl(): string
    {
        return $this->urlBuilder->getUrl('track/orderstatus/save',['_secure' => true]);
    }
}


