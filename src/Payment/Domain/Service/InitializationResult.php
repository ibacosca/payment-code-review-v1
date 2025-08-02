<?php

namespace App\Payment\Domain\Service;

use App\Payment\Domain\Entity\PaymentTransaction;

class InitializationResult
{
    public function __construct(
        public readonly string $formUrl,
    ) {
    }
}
