<?php

namespace App\Services\Billing\Data;

/**
 * Result of sending a document to SUNAT, already stripped of any Greenter
 * types so the rest of the app never needs to know about them.
 */
final class SunatSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $xml = null,
        public readonly ?string $cdrZip = null,
        public readonly ?string $code = null,
        public readonly ?string $description = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function failed(string $errorMessage, ?string $xml = null): self
    {
        return new self(success: false, xml: $xml, errorMessage: $errorMessage);
    }

    /**
     * SUNAT convention: code 0 or >= 4000 means accepted (possibly with
     * observations). Anything else is a rejection.
     */
    public function isAccepted(): bool
    {
        if (! $this->success || $this->code === null) {
            return false;
        }

        $code = (int) $this->code;

        return $code === 0 || $code >= 4000;
    }
}
