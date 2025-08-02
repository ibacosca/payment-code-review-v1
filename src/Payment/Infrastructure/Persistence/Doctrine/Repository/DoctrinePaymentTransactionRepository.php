<?php

namespace App\Payment\Infrastructure\Persistence\Doctrine\Repository;

use App\Payment\Domain\Entity\PaymentTransaction;
use App\Payment\Domain\Repository\PaymentTransactionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrinePaymentTransactionRepository extends ServiceEntityRepository implements PaymentTransactionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentTransaction::class);
    }

    public function save(PaymentTransaction $transaction): void
    {
        $this->getEntityManager()->persist($transaction);
        $this->getEntityManager()->flush();
    }

    public function findByTransactionId(string $transactionId): ?PaymentTransaction
    {
        return $this->findOneBy(['transactionId' => $transactionId]);
    }
}
