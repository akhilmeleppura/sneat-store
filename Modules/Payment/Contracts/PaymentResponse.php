<?php

namespace Modules\Payment\Contracts;

class PaymentResponse
{
    public function __construct(
        public bool $successful,
        public ?string $transactionReference = null,
        public ?string $redirectUrl = null,
        public ?string $clientSecret = null,
        public ?string $message = null,
        public array $rawPayload = []
    ) {}

    public static function success(string $reference, array $rawPayload = [], ?string $message = 'Payment successful'): self
    {
        return new self(
            successful: true,
            transactionReference: $reference,
            message: $message,
            rawPayload: $rawPayload
        );
    }

    public static function redirect(string $redirectUrl, string $reference, array $rawPayload = []): self
    {
        return new self(
            successful: true,
            transactionReference: $reference,
            redirectUrl: $redirectUrl,
            rawPayload: $rawPayload
        );
    }

    public static function failed(string $message, ?string $reference = null, array $rawPayload = []): self
    {
        return new self(
            successful: false,
            transactionReference: $reference,
            message: $message,
            rawPayload: $rawPayload
        );
    }
}
