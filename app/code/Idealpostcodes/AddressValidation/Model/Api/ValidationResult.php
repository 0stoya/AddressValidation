<?php
declare(strict_types=1);

namespace Idealpostcodes\AddressValidation\Model\Api;

use Magento\Framework\Phrase;

class ValidationResult
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly bool $valid,
        private readonly Phrase|string|null $message = null,
        private readonly array $payload = []
    ) {
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getMessage(): Phrase|string|null
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
