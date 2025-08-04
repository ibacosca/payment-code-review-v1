<?php

namespace App\Application\Handler;

use App\Application\Command\ProcessRefundCommand;
use App\Domain\Entity\PaymentTransaction;
use App\Domain\Exception\RefundFailedException;
use App\Domain\Repository\PaymentTransactionRepositoryInterface;
use App\Domain\Service\PaymentGatewayInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessRefundHandler
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly PaymentTransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function __invoke(ProcessRefundCommand $command): string
    {
        $originalTransaction = $this->transactionRepository->findByTransactionId($command->transactionId);
        if (!$originalTransaction) {
            throw new \RuntimeException('Original transaction not found for refund.');
        }

        if ($command->refundAmount > $originalTransaction->getRefundableAmount()) {
            throw new RefundFailedException('Refund amount exceeds the refundable amount.');
        }

        $refundResult = $this->paymentGateway->refund(
            $originalTransaction->getTransactionId(),
            $command->refundAmount
        );

        $refundTransaction = PaymentTransaction::createRefundFor(
            $originalTransaction,
            $command->refundAmount,
            $refundResult->transactionId
        );

        $this->transactionRepository->save($refundTransaction);

        return $refundTransaction->getTransactionId();
    }
}
