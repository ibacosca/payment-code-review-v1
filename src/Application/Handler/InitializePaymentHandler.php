<?php

namespace App\Application\Handler;

use App\Application\Command\InitializePaymentCommand;
use App\Domain\Entity\PaymentTransaction;
use App\Domain\Repository\PaymentTransactionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class InitializePaymentHandler
{
    public function __construct(
        private readonly PaymentTransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function __invoke(InitializePaymentCommand $command): string
    {
        $transaction = PaymentTransaction::initialize(
            $command->amount,
            $command->currency,
            $command->billingInfo,
        );

        if ($command->isSubscription) {
            $transaction->startSubscription('P1M');
        }

        $this->transactionRepository->save($transaction);
        
        return $transaction->getUuid();
    }
}
