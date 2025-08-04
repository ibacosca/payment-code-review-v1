<?php

namespace App\Infrastructure\Http\Api;

use App\Application\Command\RebillSubscriptionsCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class SubscriptionController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/subscriptions/rebill', name: 'subscriptions_rebill', methods: ['GET'])]
    public function rebill(): JsonResponse
    {
        $this->messageBus->dispatch(new RebillSubscriptionsCommand());

        return new JsonResponse(['message' => 'Subscription rebilling process started.']);
    }
}
