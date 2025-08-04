<?php

namespace App\Domain\Repository;

use App\Domain\Entity\PaymentTransaction;

interface PaymentTransactionRepositoryInterface
{
    public function save(PaymentTransaction $transaction): void;

    public function findByTransactionId(string $transactionId): ?PaymentTransaction;

    public function findByUuid(string $uuid): ?PaymentTransaction;

    public function findAll(): array;
}