<?php

namespace App\Infrastructure\Gateway;

use App\Domain\Entity\PaymentTransaction;
use App\Domain\Exception\PaymentDeclinedException;
use App\Domain\Exception\PaymentErrorException;
use App\Domain\Exception\RefundFailedException;
use App\Domain\Service\InitializationResult;
use App\Domain\Service\PaymentGatewayInterface;
use App\Domain\ValueObject\BillingAddress;
use DOMDocument;
use Exception;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NmiPaymentGateway implements PaymentGatewayInterface
{
    private const NMI_THREE_STEP_URL = 'https://secure.nmi.com/api/v2/three-step';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $client,
        private readonly string $nmiApiKey,
    ) {
    }

    public function initializePayment(PaymentTransaction $transaction, string $redirectUrl, BillingAddress $billingInfo): InitializationResult
    {
        $xmlRequest = new DOMDocument('1.0', 'UTF-8');
        $xmlRequest->formatOutput = true;
        $xmlSale = $xmlRequest->createElement('sale');

        // Required fields
        $this->appendXmlNode($xmlRequest, $xmlSale, 'api-key', $this->nmiApiKey);
        $this->appendXmlNode($xmlRequest, $xmlSale, 'redirect-url', $redirectUrl);
        $this->appendXmlNode($xmlRequest, $xmlSale, 'amount', number_format($transaction->getAmount(), 2, '.', ''));
        $this->appendXmlNode($xmlRequest, $xmlSale, 'ip-address', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $this->appendXmlNode($xmlRequest, $xmlSale, 'currency', $transaction->getCurrencyCode());

        // Optional order fields
        $this->appendXmlNode($xmlRequest, $xmlSale, 'order-id', $transaction->getUuid());
        $this->appendXmlNode($xmlRequest, $xmlSale, 'order-description', 'Payment Gateway Order');

        // Billing information
        $billingData = [
            'first-name' => $billingInfo->firstName,
            'last-name' => $billingInfo->lastName,
            'address1' => $billingInfo->address1,
            'address2' => $billingInfo->address2,
            'city' => $billingInfo->city,
            'state' => $billingInfo->state,
            'postal' => $billingInfo->postal,
            'country' => $billingInfo->country,
            'email' => $billingInfo->email,
            'phone' => $billingInfo->phone,
        ];

        if (!empty($billingData)) {
            $xmlBillingAddress = $xmlRequest->createElement('billing');
            foreach (array_filter($billingData) as $key => $value) {
                $this->appendXmlNode($xmlRequest, $xmlBillingAddress, $key, $value);
            }
            $xmlSale->appendChild($xmlBillingAddress);
        }

        $xmlRequest->appendChild($xmlSale);
        $data = $this->sendApiRequest($xmlRequest, self::NMI_THREE_STEP_URL);
        $gwResponse = @new SimpleXMLElement($data);

        if ((string)$gwResponse->result !== '1') {
            $this->logger->error('Step 1 failed', ['response' => $data]);
            throw new Exception('Failed to initialize payment');
        }

        return new InitializationResult((string)$gwResponse->{'form-url'});
    }

    public function completePayment(string $token): CompletionResult
    {
        $xmlRequest = new DOMDocument('1.0', 'UTF-8');
        $xmlRequest->formatOutput = true;
        $xmlCompleteTransaction = $xmlRequest->createElement('complete-action');

        $this->appendXmlNode($xmlRequest, $xmlCompleteTransaction, 'api-key', $this->nmiApiKey);
        $this->appendXmlNode($xmlRequest, $xmlCompleteTransaction, 'token-id', $token);

        $xmlRequest->appendChild($xmlCompleteTransaction);
        $data = $this->sendApiRequest($xmlRequest, self::NMI_THREE_STEP_URL);
        $gwResponse = @new SimpleXMLElement($data);

        if ((string)$gwResponse->result === '1') {
            return new CompletionResult(
                (string)$gwResponse->{'transaction-id'},
                substr((string)$gwResponse->billing->{'cc-number'}, -4),
                $token
            );
        }

        if ((string)$gwResponse->result === '2') {
            $this->logger->warning('Payment declined', ['response' => $data]);
            throw new PaymentDeclinedException((string)$gwResponse->{'result-text'});
        }

        $this->logger->error('Payment error', ['response' => $data]);
        throw new PaymentErrorException((string)$gwResponse->{'result-text'});
    }

    private function sendApiRequest(DOMDocument $xmlRequest, string $gatewayURL): string
    {
        try {
            $response = $this->client->request('POST', $gatewayURL, [
                'headers' => ['Content-Type' => 'text/xml'],
                'body' => $xmlRequest->saveXML(),
                'timeout' => 30,
                'verify_peer' => false, // Should be true in production
            ]);

            return $response->getContent();
        } catch (TransportExceptionInterface $e) {
            throw new Exception("Request failed: " . $e->getMessage(), 0, $e);
        }
    }

    private function appendXmlNode($domDocument, $parentNode, $name, $value)
    {
        $childNode = $domDocument->createElement($name);
        $childNodeValue = $domDocument->createTextNode($value);
        $childNode->appendChild($childNodeValue);
        $parentNode->appendChild($childNode);
    }

    public function refund(string $originalTransactionId, float $refundAmount): CompletionResult
    {
        if ($refundAmount <= 0) {
            throw new \InvalidArgumentException('Refund amount must be positive.');
        }

        $xmlRequest = new DOMDocument('1.0', 'UTF-8');
        $xmlRequest->formatOutput = true;
        $xmlRefund = $xmlRequest->createElement('refund');

        $this->appendXmlNode($xmlRequest, $xmlRefund, 'api-key', $this->nmiApiKey);
        $this->appendXmlNode($xmlRequest, $xmlRefund, 'transaction-id', $originalTransactionId);
        $this->appendXmlNode($xmlRequest, $xmlRefund, 'amount', number_format($refundAmount, 2, '.', ''));

        $xmlRequest->appendChild($xmlRefund);

        $data = $this->sendApiRequest($xmlRequest, self::NMI_THREE_STEP_URL);
        $gwResponse = @new SimpleXMLElement($data);

        if ((string) $gwResponse->result !== '1') {
            $message = (string) $gwResponse->{'result-text'} ?: 'Refund failed.';
            $this->logger->warning(
                'Refund failed',
                ['response_text' => $message, 'original_transaction_id' => $originalTransactionId],
            );
            throw new RefundFailedException($message);
        }

        $this->logger->info(
            'Refund successful',
            [
                'transaction_id' => (string) $gwResponse->{'transaction-id'},
                'original_transaction_id' => $originalTransactionId,
            ],
        );

        return new CompletionResult(
            (string) $gwResponse->{'transaction-id'},
            '****', // Last 4 digits are not available in refund responses
            '' // Token is not returned in refund responses
        );
    }

    public function chargeWithToken(float $amount, string $currency, string $token, BillingAddress $billingAddress): PaymentTransaction
    {
        $xmlRequest = new DOMDocument('1.0', 'UTF-8');
        $xmlRequest->formatOutput = true;
        $xmlSale = $xmlRequest->createElement('sale');

        $this->appendXmlNode($xmlRequest, $xmlSale, 'api-key', $this->nmiApiKey);
        $this->appendXmlNode($xmlRequest, $xmlSale, 'amount', number_format($amount, 2, '.', ''));
        $this->appendXmlNode($xmlRequest, $xmlSale, 'currency', $currency);
        $this->appendXmlNode($xmlRequest, $xmlSale, 'payment-token', $token);

        $xmlRequest->appendChild($xmlSale);

        $data = $this->sendApiRequest($xmlRequest, self::NMI_THREE_STEP_URL);
        $gwResponse = @new SimpleXMLElement($data);

        if ((string) $gwResponse->result === '1') {
            $transaction = PaymentTransaction::initialize($amount, $currency, $billingAddress);
            $transaction->complete(
                (string) $gwResponse->{'transaction-id'},
                '****', // Last 4 digits are not available in this flow
                $token
            );
            $transaction->startSubscription('P1M'); // Mark as a subscription renewal

            return $transaction;
        }

        if ((string) $gwResponse->result === '2') {
            $this->logger->warning('Rebill payment declined', ['response' => $data]);
            throw new PaymentDeclinedException((string) $gwResponse->{'result-text'});
        }

        $this->logger->error('Rebill payment error', ['response' => $data]);
        throw new PaymentErrorException((string) $gwResponse->{'result-text'});
    }
}
