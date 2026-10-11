<?php

namespace App\Services\DocumentVerification;

final readonly class VerificationResult
{
    public function __construct(
        public string $status,
        public string $reference,
        public string $message,
    ) {
        if (! in_array($status, ['approved', 'rejected', 'error'], true)) {
            throw new \InvalidArgumentException('Unsupported verification result.');
        }
    }
}
