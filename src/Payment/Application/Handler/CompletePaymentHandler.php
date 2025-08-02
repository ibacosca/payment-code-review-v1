<?php

namespace App\Payment\Application\Handler;

use App\Payment\Application\Command\CompletePaymentCommand;
use App\Payment\Domain\Repository\PaymentTransactionRepositoryInterface;
use App\Payment\Domain\Service\PaymentGatewayInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CompletePaymentHandler
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly PaymentTransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function __invoke(CompletePaymentCommand $command): string
    {
        $transaction = $this->paymentGateway->completePayment($command->tokenId);
        $this->transactionRepository->save($transaction);

        return $transaction->getTransactionId();
    }
}
