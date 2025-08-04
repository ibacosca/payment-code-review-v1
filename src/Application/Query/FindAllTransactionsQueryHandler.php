<?php

namespace App\Application\Query;

use App\Domain\Repository\PaymentTransactionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FindAllTransactionsQueryHandler
{
    public function __construct(
        private readonly PaymentTransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function __invoke(FindAllTransactionsQuery $query): array
    {
        return $this->transactionRepository->findAll();
    }
}
