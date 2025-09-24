<?php
declare(strict_types=1);

namespace Idealpostcodes\AddressValidation\Plugin\Checkout;

use Idealpostcodes\AddressValidation\Model\AddressDataExtractor;
use Idealpostcodes\AddressValidation\Model\Api\AddressValidator;
use Idealpostcodes\AddressValidation\Model\Api\ValidationResult;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use function __;

class ShippingInformationManagementPlugin
{
    public function __construct(
        private readonly AddressDataExtractor $dataExtractor,
        private readonly AddressValidator $addressValidator
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ): array {
        $shipping = $addressInformation->getShippingAddress();
        if ($shipping) {
            $this->validateAddress($shipping, $shipping->getStoreId());
        }

        $billing = $addressInformation->getBillingAddress();
        if ($billing) {
            $storeId = method_exists($billing, 'getStoreId') ? $billing->getStoreId() : ($shipping ? $shipping->getStoreId() : null);
            $this->validateAddress($billing, $storeId);
        }

        return [$cartId, $addressInformation];
    }

    /**
     * @param \Magento\Framework\Api\ExtensibleDataInterface $address
     * @throws LocalizedException
     */
    private function validateAddress($address, ?int $storeId = null): void
    {
        $addressData = $this->dataExtractor->extract($address);
        $result = $this->addressValidator->validate($addressData, $storeId);
        if (!$result->isValid()) {
            throw new LocalizedException($this->resolveMessage($result));
        }
    }

    private function resolveMessage(ValidationResult $result): Phrase
    {
        $message = $result->getMessage();
        if ($message instanceof Phrase) {
            return $message;
        }

        if (is_string($message) && $message !== '') {
            return __($message);
        }

        return __('We could not validate the provided address.');
    }
}
