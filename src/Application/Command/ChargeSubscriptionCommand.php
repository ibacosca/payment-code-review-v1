<?php

namespace App\Application\Command;

class ChargeSubscriptionCommand
{
    public function __construct(
        public readonly int $subscriptionId,
    ) {
    }
}
