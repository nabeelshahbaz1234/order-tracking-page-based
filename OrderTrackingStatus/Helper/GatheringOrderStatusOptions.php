<?php

declare(strict_types=1);

namespace RltSquare\OrderTrackingStatus\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use RltSquare\OrderTrackingStatus\Logger\Logger;
use RltSquare\OrderTrackingStatus\Action\BigBuyResponseProcessors;
use RltSquare\OrderTrackingStatus\Model\Config;


/**
 * @class GatheringOrderStatusOptions
 */
class GatheringOrderStatusOptions
{
    private Logger $logger;
    private Config $config;
    private BigBuyResponseProcessors $responseProcessors;

    /**
     * @param Logger $logger
     * @param Config $config
     * @param BigBuyResponseProcessors $responseProcessors
     */
    public function __construct(
        Logger                   $logger,
        Config                   $config,
        BigBuyResponseProcessors $responseProcessors,
    )
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->responseProcessors = $responseProcessors;

    }

    /**
     * @throws LocalizedException
     */

    public function gatheringOrderData($bigBuyOrderId): array
    {

        if ($this->config->isEnabled()) {
            $apiToken = $this->config->getApiToken();

            if ($bigBuyOrderId != 0) {
                $apiUrl = $this->config->getApiUrl() . '/' . $bigBuyOrderId . '.json';
                $client = new Client();
                $options = [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $apiToken,
                    ],
                ];

                try {
                    $response = $client->get($apiUrl, $options);
                    return $this->responseProcessors->processResponse($response);
                } catch (GuzzleException $guzzleException) {
                    $this->logger->error($guzzleException->getMessage());
                }
            }
        } else {
            $this->logger->error('BigBuy export module is disabled');
        }

        return [];
    }

}
