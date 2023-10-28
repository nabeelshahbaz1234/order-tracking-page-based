<?php
declare(strict_types=1);


namespace RltSquare\OrderTrackingStatus\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @Config
 */
class Config
{
    /**
     * @const CONFIG_PATH_ENABLED
     */
    const CONFIG_PATH_ENABLED = 'sales/order_status/enabled';
    const CONFIG_PATH_API_TOKEN = 'sales/order_status/api_token';
    const CONFIG_PATH_API_URL = 'sales/order_status/api_url';

     // vidaXl order status
    const VIDA_XL_CONFIG_PATH_ENABLED = 'sales/VidaXl_order_status/enabled';
    const VIDA_XL_CONFIG_PATH_API_URL = 'sales/VidaXl_order_status/api_url';
    const CONFIG_PATH_VidaXL_API_TOKEN = 'sales/VidaXl_order_status/api_token';
    const CONFIG_PATH_VidaXL_API_USERNAME = 'sales/VidaXl_order_status/user_name';


    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return bool
     */
    public function isEnabled(string $scopeType = ScopeInterface::SCOPE_STORE, ?string $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return string
     */
    public function getApiToken(string $scopeType = ScopeInterface::SCOPE_STORE, ?string $scopeCode = null): string
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_API_TOKEN, $scopeType, $scopeCode);
        return ($value !== null) ? (string)$value : '';
    }

    /**
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return string
     */
    public function getApiUrl(string $scopeType = ScopeInterface::SCOPE_STORE, ?string $scopeCode = null): string
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_API_URL, $scopeType, $scopeCode);
        return ($value !== null) ? (string)$value : '';
    }


    public function vidaXlIsEnabled(string $scopeType = ScopeInterface::SCOPE_STORE, ?string $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::VIDA_XL_CONFIG_PATH_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return string
     */
    public function vidaXlGetApiToken(string $scopeType = ScopeInterface::SCOPE_STORE, ?string $scopeCode = null): string
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_VidaXL_API_TOKEN, $scopeType, $scopeCode);
        return ($value !== null) ? (string)$value : '';
    }

    /**
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return string
     */
    public function GetApiUserName(string $scopeType = ScopeInterface::SCOPE_STORE, ?string $scopeCode = null): string
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_VidaXL_API_USERNAME, $scopeType, $scopeCode);
        return ($value !== null) ? (string)$value : '';
    }

    /**
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return string
     */
    public function vidaXlGetApiUrl(string $scopeType = ScopeInterface::SCOPE_STORE, ?string $scopeCode = null): string
    {
        $value = $this->scopeConfig->getValue(self::VIDA_XL_CONFIG_PATH_API_URL, $scopeType, $scopeCode);
        return ($value !== null) ? (string)$value : '';
    }
}
