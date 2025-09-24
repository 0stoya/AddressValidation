<?php
declare(strict_types=1);

namespace Idealpostcodes\AddressValidation\Plugin\Checkout;

use Idealpostcodes\AddressValidation\Model\AddressDataExtractor;
use Idealpostcodes\AddressValidation\Model\Api\AddressValidator;
use Idealpostcodes\AddressValidation\Model\Api\ValidationResult;
use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use function __;

class GuestPaymentInformationManagementPlugin
{
    public function __construct(
        private readonly AddressDataExtractor $dataExtractor,
        private readonly AddressValidator $addressValidator
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function beforeSavePaymentInformation(
        GuestPaymentInformationManagement $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        if ($billingAddress) {
            $this->validateAddress($billingAddress, $billingAddress->getStoreId());
        }

        return [$cartId, $email, $paymentMethod, $billingAddress];
    }

    /**
     * @throws LocalizedException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagement $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        if ($billingAddress) {
            $this->validateAddress($billingAddress, $billingAddress->getStoreId());
        }

        return [$cartId, $email, $paymentMethod, $billingAddress];
    }

    /**
     * @throws LocalizedException
     */
    private function validateAddress(AddressInterface $address, ?int $storeId = null): void
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
