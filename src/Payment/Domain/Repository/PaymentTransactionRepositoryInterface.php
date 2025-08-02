<?php

namespace App\Payment\Domain\Repository;

use App\Payment\Domain\Entity\PaymentTransaction;

interface PaymentTransactionRepositoryInterface
{
    public function save(PaymentTransaction $transaction): void;

    public function findByTransactionId(string $transactionId): ?PaymentTransaction;
}
