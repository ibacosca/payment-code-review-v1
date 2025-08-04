<?php

namespace App\Domain\Service;

use App\Domain\Entity\PaymentTransaction;

class InitializationResult
{
    public function __construct(
        public readonly string $formUrl,
    ) {
    }
}
