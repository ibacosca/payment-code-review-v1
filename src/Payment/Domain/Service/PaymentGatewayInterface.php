<?php

namespace App\Payment\Domain\Service;

use App\Payment\Domain\Entity\PaymentTransaction;
use App\Payment\Domain\ValueObject\BillingAddress;

interface PaymentGatewayInterface
{
    public function initializePayment(PaymentTransaction $transaction, string $redirectUrl, BillingAddress $billingInfo): InitializationResult;
    public function completePayment(string $token): PaymentTransaction;
    public function refund(string $originalTransactionId, float $refundAmount): string;
}
