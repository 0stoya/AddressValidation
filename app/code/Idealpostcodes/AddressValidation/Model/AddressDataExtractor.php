<?php
declare(strict_types=1);

namespace Idealpostcodes\AddressValidation\Model;

use Magento\Customer\Api\Data\RegionInterface as CustomerRegionInterface;
use Magento\Directory\Api\Data\RegionInterface;
use Magento\Framework\Api\ExtensibleDataInterface;

class AddressDataExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extract(ExtensibleDataInterface $address): array
    {
        $street = [];
        if (method_exists($address, 'getStreet')) {
            $streetData = $address->getStreet();
            if (is_array($streetData)) {
                $street = $streetData;
            } elseif ($streetData !== null) {
                $street = [(string) $streetData];
            }
        }

        return [
            'street' => $street,
            'city' => method_exists($address, 'getCity') ? (string) $address->getCity() : '',
            'region' => $this->extractRegion($address),
            'postcode' => method_exists($address, 'getPostcode') ? (string) $address->getPostcode() : '',
            'country_id' => method_exists($address, 'getCountryId') ? (string) $address->getCountryId() : '',
        ];
    }

    private function extractRegion(ExtensibleDataInterface $address): string
    {
        if (method_exists($address, 'getRegion')) {
            $region = $address->getRegion();
            if (is_string($region)) {
                return $region;
            }

            if ($region instanceof CustomerRegionInterface || $region instanceof RegionInterface) {
                $regionName = $region->getRegion();
                if ($regionName) {
                    return $regionName;
                }

                $regionCode = $region->getRegionCode();
                if ($regionCode) {
                    return $regionCode;
                }
            }

            if (is_object($region) && method_exists($region, 'getRegion')) {
                $regionName = $region->getRegion();
                if (is_string($regionName) && $regionName !== '') {
                    return $regionName;
                }
            }
        }

        return '';
    }
}
