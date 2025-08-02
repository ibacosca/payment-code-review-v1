<?php

namespace App\Payment\Application\Command;

use App\Payment\Application\DTO\BillingInfoDTO;

class InitializePaymentCommand
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly BillingInfoDTO $billingInfo,
        public readonly string $redirectUrl,
    ) {
    }
}
