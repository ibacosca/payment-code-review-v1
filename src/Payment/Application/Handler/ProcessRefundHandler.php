<?php

namespace App\Payment\Application\Handler;

use App\Payment\Application\Command\ProcessRefundCommand;
use App\Payment\Domain\Service\PaymentGatewayInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessRefundHandler
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {
    }

    public function __invoke(ProcessRefundCommand $command): string
    {
        return $this->paymentGateway->refund($command->transactionId, $command->refundAmount);
    }
}
