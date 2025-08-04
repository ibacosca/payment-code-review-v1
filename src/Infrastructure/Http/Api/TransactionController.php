<?php

namespace App\Infrastructure\Http\Api;

use App\Application\Query\FindAllTransactionsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class TransactionController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly SerializerInterface $serializer,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/transactions', name: 'transactions_list', methods: ['GET'])]
    public function getTransactions(): JsonResponse
    {
        $transactions = $this->handle(new FindAllTransactionsQuery());

        $json = $this->serializer->serialize($transactions, 'json', ['groups' => 'transaction:read']);

        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
    }
}
