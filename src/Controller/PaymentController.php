<?php

namespace App\Controller;

use App\Form\CheckoutType;
use App\Form\RefundType;
use App\Form\Step2Type;
use App\Repository\PaymentTransactionRepository;
use App\Service\NmiPaymentGateway;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController
{
    private NmiPaymentGateway $paymentGateway;
    private LoggerInterface $logger;

    public function __construct(NmiPaymentGateway $paymentGateway, LoggerInterface $logger)
    {
        $this->paymentGateway = $paymentGateway;
        $this->logger = $logger;
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(Request $request)
    {
        // Handle token-id from Step 3
        if ($request->query->get('token-id')) {
            $result = $this->paymentGateway->completeTransaction($request);

            if ($result['status'] === 'success') {
                $this->addFlash('success', 'Payment successful! Transaction ID: ' . $result['transaction_id']);
                $this->logger->info('Checkout successful', ['transaction_id' => $result['transaction_id']]);
            } elseif ($result['status'] === 'declined') {
                $this->addFlash('danger', 'Payment declined: ' . $result['decline_message']);
                $this->logger->warning('Payment declined', ['decline_message' => $result['decline_message']]);
            } else {
                $this->addFlash('danger', 'Payment failed: ' . $result['error_message']);
                $this->logger->error('Checkout failed', ['error_message' => $result['error_message']]);
            }

            return $this->redirectToRoute('app_checkout');
        }

        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Step 1: Initialize payment
            $redirectUrl = $this->generateUrl('app_checkout', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

            $billingInfo = [
                'first-name' => $data['billingFirstName'],
                'last-name' => $data['billingLastName'],
                'address1' => $data['billingAddress1'] ?? '',
                'address2' => $data['billingAddress2'] ?? '',
                'city' => $data['billingCity'] ?? '',
                'state' => $data['billingState'] ?? '',
                'postal' => $data['billingPostal'],
                'country' => $data['billingCountry'],
                'email' => $data['billingEmail'] ?? '',
                'phone' => $data['billingPhone'] ?? ''
            ];

            $result = $this->paymentGateway->initializePayment(
                $data['amount'],
                $data['currency'],
                $redirectUrl,
                $billingInfo
            );

            if ($result['status'] === 'success') {
                // Store amount in session for Step 2
                $request->getSession()->set('payment_amount', $data['amount']);

                // Create Step 2 form with the NMI form URL as action
                $step2Form = $this->createForm(Step2Type::class, null, [
                    'action' => $result['form_url']
                ]);

                // Render Step 2 form
                return $this->render('payment/step2.html.twig', [
                    'step2Form' => $step2Form->createView(),
                    'amount' => $data['amount']
                ]);
            } else {
                $this->addFlash('danger', 'Failed to initialize payment: ' . $result['message']);
                $this->logger->error('Step 1 failed', ['message' => $result['message']]);
            }
        }

        return $this->render('payment/checkout.html.twig', [
            'checkoutForm' => $form->createView(),
        ]);
    }

    #[Route('/refund', name: 'app_refund')]
    public function refund(Request $request)
    {
        $form = $this->createForm(RefundType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $result = $this->paymentGateway->processRefund(
                $data['transactionId'],
                $data['refundAmount']
            );

            if ($result['status'] === 'success') {
                $this->addFlash('success', 'Refund successful! New Transaction ID: ' . $result['transaction_id']);
                $this->logger->info('Refund successful', ['transaction_id' => $result['transaction_id']]);
                return $this->redirectToRoute('app_refund');
            } else {
                $this->addFlash('danger', 'Refund failed: ' . $result['message']);
                $this->logger->error('Refund failed', ['message' => $result['message']]);
                return $this->redirectToRoute('app_refund');
            }
        }

        return $this->render('payment/refund.html.twig', [
            'refundForm' => $form->createView(),
        ]);
    }

    #[Route('/api/transactions', name: 'app_payment_history')]
    public function getTransactions(Request $request, PaymentTransactionRepository $repository)
    {
        $transactions = $repository->by($request);

        foreach ($transactions as $transaction) {
            $transactionData = [
                'uuid' => $transaction->getUuid(),
                'transaction_id' => $transaction->getTransactionId(),
                'amount' => $transaction->getAmount(),
                'currency_code' => $transaction->getCurrencyCode(),
                'payment_status' => $transaction->getPaymentStatus(),
                'last4_digits' => $transaction->getLast4Digits(),
                'created_at' => $transaction->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
            $transactions[] = $transactionData;
        }

        return new Response(
            json_encode($transactions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
        );
    }
}
