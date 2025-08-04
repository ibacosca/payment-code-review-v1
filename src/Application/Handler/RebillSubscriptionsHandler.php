<?php

namespace App\Application\Handler;

use App\Application\Command\ChargeSubscriptionCommand;
use App\Application\Command\RebillSubscriptionsCommand;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class RebillSubscriptionsHandler
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(RebillSubscriptionsCommand $command): void
    {
        $now = new \DateTimeImmutable();
        $dueSubscriptions = $this->subscriptionRepository->findDueSubscriptions($now);

        foreach ($dueSubscriptions as $subscription) {
            $this->messageBus->dispatch(new ChargeSubscriptionCommand(
                $subscription->getId()
            ));
        }
    }
}
