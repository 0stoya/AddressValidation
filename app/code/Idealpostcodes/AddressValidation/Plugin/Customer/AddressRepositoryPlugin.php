<?php
declare(strict_types=1);

namespace Idealpostcodes\AddressValidation\Plugin\Customer;

use Idealpostcodes\AddressValidation\Model\AddressDataExtractor;
use Idealpostcodes\AddressValidation\Model\Api\AddressValidator;
use Idealpostcodes\AddressValidation\Model\Api\ValidationResult;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use function __;

class AddressRepositoryPlugin
{
    public function __construct(
        private readonly AddressDataExtractor $dataExtractor,
        private readonly AddressValidator $addressValidator
    ) {
    }

    /**
     * @param AddressRepositoryInterface $subject
     * @param AddressInterface $address
     * @throws LocalizedException
     */
    public function beforeSave(AddressRepositoryInterface $subject, AddressInterface $address): array
    {
        $addressData = $this->dataExtractor->extract($address);
        $storeId = method_exists($address, 'getStoreId') ? $address->getStoreId() : null;
        $result = $this->addressValidator->validate($addressData, $storeId !== null ? (int) $storeId : null);
        if (!$result->isValid()) {
            throw new LocalizedException($this->resolveMessage($result));
        }

        return [$address];
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
