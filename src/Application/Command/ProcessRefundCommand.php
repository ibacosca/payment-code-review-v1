<?php

namespace App\Application\Command;

class ProcessRefundCommand
{
    public function __construct(
        public readonly string $transactionId,
        public readonly float $refundAmount,
    ) {
    }
}
