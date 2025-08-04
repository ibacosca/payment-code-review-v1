<?php

namespace App\Application\Command;

use App\Domain\ValueObject\BillingAddress;

class InitializePaymentCommand
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly BillingAddress $billingInfo,
        public readonly bool $isSubscription,
    ) {
    }
}
