<?php

namespace App\Infrastructure\Gateway;

class CompletionResult
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $last4Digits,
        public readonly string $usedToken,
    ) {
    }
}
