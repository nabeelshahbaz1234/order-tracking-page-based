<?php

declare(strict_types=1);

namespace RltSquare\OrderTrackingStatus\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use RltSquare\OrderTrackingStatus\Action\VidaXlResponseProcessors;
use RltSquare\OrderTrackingStatus\Logger\Logger;
use RltSquare\OrderTrackingStatus\Model\Config;


/**
 * @class GatheringOrderStatusOptions
 */
class GatheringVidaXlOrderStatusOptions
{
    private Logger $logger;
    private Config $config;
    private VidaXlResponseProcessors $responseProcessors;

    /**
     * @param Logger $logger
     * @param Config $config
     * @param VidaXlResponseProcessors $responseProcessors
     */
    public function __construct(
        Logger                   $logger,
        Config                   $config,
        VidaXlResponseProcessors $responseProcessors,
    )
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->responseProcessors = $responseProcessors;

    }

    /**
     * @throws LocalizedException
     */

    public function gatheringVidaXlOrderData($magentoOrderId): array
    {

        if ($this->config->vidaXlIsEnabled()) {

            $apiUserName = $this->config->GetApiUserName();
            $apiToken = $this->config->vidaXlGetApiToken();

            $authCredentials = [
                'Username' => $apiUserName,
                'Password' => $apiToken
            ];
            if ($magentoOrderId != 0) {
                $apiUrl = $this->config->vidaXlGetApiUrl() . '?customer_order_reference_eq=' . $magentoOrderId;
                $client = new Client();
                $options = [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Basic ' . base64_encode($authCredentials['Username'] . ':' . $authCredentials['Password']),
                        'Accept' => 'application/json',
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
            $this->logger->error('Vida Xl export module is disabled');
        }

        return [];
    }

}
