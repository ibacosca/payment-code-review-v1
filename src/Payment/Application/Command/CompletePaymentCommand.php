<?php

namespace App\Payment\Application\Command;

class CompletePaymentCommand
{
    public function __construct(
        public readonly string $tokenId,
    ) {
    }
}
