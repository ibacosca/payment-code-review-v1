<?php

namespace App\Application\Handler;

use App\Application\Command\ChargeSubscriptionCommand;
use App\Domain\Exception\PaymentDeclinedException;
use App\Domain\Repository\PaymentTransactionRepositoryInterface;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Service\PaymentGatewayInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ChargeSubscriptionHandler
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PaymentTransactionRepositoryInterface $transactionRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ChargeSubscriptionCommand $command): void
    {
        $subscription = $this->subscriptionRepository->find($command->subscriptionId);

        if (!$subscription) {
            $this->logger->error('Subscription not found for charging.', ['subscriptionId' => $command->subscriptionId]);
            return;
        }

        try {
            $initialTransaction = $subscription->getInitialTransaction();
            $billingAddress = $initialTransaction->getBillingAddressValueObject();

            $newTransaction = $this->paymentGateway->chargeWithToken(
                $subscription->getAmount(),
                $subscription->getCurrency(),
                $subscription->getPaymentToken(),
                $billingAddress
            );

            $this->transactionRepository->save($newTransaction);
            $subscription->updateNextBillingDate();

            $this->logger->info('Subscription rebilled successfully.', ['subscriptionId' => $subscription->getId(), 'newTransactionId' => $newTransaction->getTransactionId()]);

        } catch (PaymentDeclinedException $e) {
            $this->logger->warning('Subscription rebill declined.', ['subscriptionId' => $subscription->getId(), 'reason' => $e->getMessage()]);
            $subscription->fail();
        } catch (\Exception $e) {
            $this->logger->error('An error occurred during subscription rebill.', ['subscriptionId' => $subscription->getId(), 'exception' => $e]);
            $subscription->fail();
        }

        $this->subscriptionRepository->save($subscription);
    }
}
