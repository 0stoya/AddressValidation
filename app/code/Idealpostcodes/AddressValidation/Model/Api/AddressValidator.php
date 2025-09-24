<?php
declare(strict_types=1);

namespace Idealpostcodes\AddressValidation\Model\Api;

use Idealpostcodes\AddressValidation\Model\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use function __;

class AddressValidator
{
    private const ENDPOINT = 'https://api.ideal-postcodes.co.uk/v1/autocomplete/addresses';

    public function __construct(
        private readonly Curl $curl,
        private readonly Json $serializer,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $addressData
     */
    public function validate(array $addressData, ?int $storeId = null): ValidationResult
    {
        if (!$this->config->isEnabled($storeId)) {
            return new ValidationResult(true);
        }

        $apiKey = $this->config->getApiKey($storeId);
        if ($apiKey === '') {
            $this->logger->warning('Ideal Postcodes validation skipped: API key not configured.');
            return new ValidationResult(true);
        }

        if (!$this->isCountryAllowed($addressData, $storeId)) {
            return new ValidationResult(true);
        }

        $query = $this->buildQuery($addressData);
        if ($query === '') {
            return new ValidationResult(true);
        }

        $params = [
            'api_key' => $apiKey,
            'query' => $query,
            'limit' => 1,
        ];

        if (!empty($addressData['postcode'])) {
            $params['filters[postcode]'] = strtoupper(trim((string) $addressData['postcode']));
        }

        try {
            $this->curl->reset();
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->setTimeout(5);
            $this->curl->get(self::ENDPOINT, $params);
        } catch (\Throwable $exception) {
            $this->logger->error('Ideal Postcodes validation failed', ['exception' => $exception]);
            return new ValidationResult(false, __('We could not validate your address at this time. Please try again.'));
        }

        $status = $this->curl->getStatus();
        $body = $this->curl->getBody();

        if ($status !== 200) {
            $this->logger->error('Ideal Postcodes API returned non-success status', ['status' => $status, 'response' => $body]);
            return new ValidationResult(false, __('We could not validate your address. Please review the details and try again.'));
        }

        try {
            $decoded = $this->serializer->unserialize($body);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error('Ideal Postcodes API response could not be decoded', ['exception' => $exception, 'response' => $body]);
            return new ValidationResult(false, __('We could not validate your address. Please verify it and try again.'));
        }

        if (!is_array($decoded) || empty($decoded['suggestions']) || !is_array($decoded['suggestions'])) {
            return new ValidationResult(false, __('We could not find a matching address. Please check the details.'));
        }

        $minimumScore = $this->config->getMinimumScore($storeId);
        $postcode = isset($addressData['postcode']) ? strtoupper(trim((string) $addressData['postcode'])) : '';
        foreach ($decoded['suggestions'] as $suggestion) {
            if (!is_array($suggestion)) {
                continue;
            }
            $score = 1.0;
            if (isset($suggestion['score'])) {
                $score = (float) $suggestion['score'];
            } elseif (isset($suggestion['confidence'])) {
                $score = (float) $suggestion['confidence'];
            }
            if ($score < $minimumScore) {
                continue;
            }
            if ($postcode !== '') {
                $suggestionPostcode = '';
                if (isset($suggestion['postcode'])) {
                    $suggestionPostcode = (string) $suggestion['postcode'];
                } elseif (isset($suggestion['postcode_out'], $suggestion['postcode_in'])) {
                    $suggestionPostcode = sprintf('%s %s', $suggestion['postcode_out'], $suggestion['postcode_in']);
                } elseif (isset($suggestion['postcode_out'])) {
                    $suggestionPostcode = (string) $suggestion['postcode_out'];
                }

                if ($suggestionPostcode !== '' && strtoupper(trim($suggestionPostcode)) !== $postcode) {
                    continue;
                }
            }

            return new ValidationResult(true, null, ['suggestion' => $suggestion]);
        }

        return new ValidationResult(false, __('We could not validate your address. Please double-check the information.'));
    }

    /**
     * @param array<string, mixed> $addressData
     */
    private function buildQuery(array $addressData): string
    {
        $parts = [];
        if (!empty($addressData['street'])) {
            if (is_array($addressData['street'])) {
                foreach ($addressData['street'] as $line) {
                    $line = trim((string) $line);
                    if ($line !== '') {
                        $parts[] = $line;
                    }
                }
            } else {
                $street = trim((string) $addressData['street']);
                if ($street !== '') {
                    $parts[] = $street;
                }
            }
        }

        foreach (['city', 'region', 'postcode'] as $field) {
            if (!empty($addressData[$field])) {
                $value = trim((string) $addressData[$field]);
                if ($value !== '') {
                    $parts[] = $value;
                }
            }
        }

        if (!empty($addressData['country_id'])) {
            $parts[] = (string) $addressData['country_id'];
        }

        $parts = array_unique($parts);

        return implode(', ', $parts);
    }

    /**
     * @param array<string, mixed> $addressData
     */
    private function isCountryAllowed(array $addressData, ?int $storeId = null): bool
    {
        $allowedCountries = $this->config->getAllowedCountries($storeId);
        if ($allowedCountries === []) {
            return true;
        }

        $countryId = isset($addressData['country_id']) ? strtoupper((string) $addressData['country_id']) : '';
        $allowedCountries = array_map('strtoupper', $allowedCountries);

        return $countryId === '' || in_array($countryId, $allowedCountries, true);
    }
}
