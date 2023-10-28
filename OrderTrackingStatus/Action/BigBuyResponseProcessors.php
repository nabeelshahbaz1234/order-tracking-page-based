<?php

declare(strict_types=1);

namespace RltSquare\OrderTrackingStatus\Action;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Psr\Http\Message\ResponseInterface;
use function json_decode;

/**
 * @class ResponseProcessors
 */
class BigBuyResponseProcessors
{
    /**
     * @param ResponseInterface $response
     * @return array
     * @throws LocalizedException
     */
    public function processResponse(ResponseInterface $response): array
    {
        $responseBody = (string)$response->getBody();
        try {
            $responseData = json_decode($responseBody, true);
        } catch (Exception $e) {
            $responseData = [];
        }
        if ($response->getStatusCode() !== 200) {
            throw new LocalizedException(__('There was Problem making Connection!'));
        }
        return $responseData;
    }
}
