<?php

namespace App\Payment\Application\Handler;

use App\Payment\Application\Command\InitializePaymentCommand;
use App\Payment\Domain\Entity\PaymentTransaction;
use App\Payment\Domain\Repository\PaymentTransactionRepositoryInterface;
use App\Payment\Domain\Service\PaymentGatewayInterface;
use App\Payment\Domain\ValueObject\BillingAddress;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class InitializePaymentHandler
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly PaymentTransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function __invoke(InitializePaymentCommand $command): string
    {
        $transaction = PaymentTransaction::initialize($command->amount, $command->currency);
        $this->transactionRepository->save($transaction);

        $billingAddress = new BillingAddress(
            firstName: $command->billingInfo->firstName,
            lastName: $command->billingInfo->lastName,
            postal: $command->billingInfo->postal,
            country: $command->billingInfo->country,
            address1: $command->billingInfo->address1,
            address2: $command->billingInfo->address2,
            city: $command->billingInfo->city,
            state: $command->billingInfo->state,
            email: $command->billingInfo->email,
            phone: $command->billingInfo->phone,
        );

        $initializationResult = $this->paymentGateway->initializePayment($transaction, $command->redirectUrl, $billingAddress);

        return $initializationResult->formUrl;
    }
}
