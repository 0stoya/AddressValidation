<?php
declare(strict_types=1);

namespace Idealpostcodes\AddressValidation\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'idealpostcodes/general/enabled';
    private const XML_PATH_API_KEY = 'idealpostcodes/general/api_key';
    private const XML_PATH_COUNTRY_FILTER = 'idealpostcodes/general/country_filter';
    private const XML_PATH_MINIMUM_SCORE = 'idealpostcodes/general/minimum_score';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getApiKey(?int $storeId = null): string
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_API_KEY, ScopeInterface::SCOPE_STORE, $storeId);

        return $value !== '' ? $this->encryptor->decrypt($value) : '';
    }

    /**
     * @return string[]
     */
    public function getAllowedCountries(?int $storeId = null): array
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_COUNTRY_FILTER, ScopeInterface::SCOPE_STORE, $storeId);
        if ($value === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $value)));
    }

    public function getMinimumScore(?int $storeId = null): float
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_MINIMUM_SCORE, ScopeInterface::SCOPE_STORE, $storeId);
        return $value === '' ? 0.0 : (float) $value;
    }
}
