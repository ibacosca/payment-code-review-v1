<?php

namespace App\Application\Handler;

use App\Application\Command\CompletePaymentCommand;
use App\Domain\Entity\Subscription;
use App\Domain\Repository\PaymentTransactionRepositoryInterface;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Service\PaymentGatewayInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CompletePaymentHandler
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly PaymentTransactionRepositoryInterface $transactionRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
    ) {
    }

    public function __invoke(CompletePaymentCommand $command): string
    {
        $transaction = $this->transactionRepository->findByUuid($command->transactionUuid);
        if (!$transaction) {
            throw new \RuntimeException('Transaction not found');
        }

        $isSubscription = $transaction->isSubscription();

        $completionResult = $this->paymentGateway->completePayment($command->tokenId);

        $transaction->complete(
            $completionResult->transactionId,
            $completionResult->last4Digits,
            $completionResult->usedToken
        );

        $this->transactionRepository->save($transaction);

        if ($isSubscription) {
            $subscription = new Subscription($transaction);
            $this->subscriptionRepository->save($subscription);
        }

        return $transaction->getTransactionId();
    }
}
