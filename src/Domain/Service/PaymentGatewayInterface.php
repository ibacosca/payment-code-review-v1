<?php

namespace App\Domain\Service;

use App\Domain\Entity\PaymentTransaction;
use App\Domain\ValueObject\BillingAddress;
use App\Infrastructure\Gateway\CompletionResult;

interface PaymentGatewayInterface
{
    public function initializePayment(PaymentTransaction $transaction, string $redirectUrl, BillingAddress $billingInfo): InitializationResult;
    public function completePayment(string $token): CompletionResult;
    public function refund(string $originalTransactionId, float $refundAmount): CompletionResult;
    public function chargeWithToken(float $amount, string $currency, string $token, BillingAddress $billingAddress): PaymentTransaction;
}
