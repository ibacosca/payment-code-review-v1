<?php

namespace App\Repository;

use App\Entity\PaymentTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<PaymentTransaction>
 */
class PaymentTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentTransaction::class);
    }

    public function by(Request $request)
    {
        $values = $request->get('transaction-id');

        if ($values) {
            $messages = $this->getEntityManager()
                ->createQuery(sprintf("SELECT t FROM App\Entity\PaymentTransaction t WHERE t.transaction_id = '%s';", $values))
                ->getResult();
        } else {
            $messages = $this->findAll();
        }

        return $messages;
    }
}
