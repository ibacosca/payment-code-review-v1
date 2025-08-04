<?php

namespace App\Application\Command;

class CompletePaymentCommand
{
    public function __construct(
        public readonly string $tokenId,
        public readonly string $transactionUuid,
    ) {
    }
}
