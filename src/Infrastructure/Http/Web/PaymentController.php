<?php

namespace App\Infrastructure\Http\Web;

use App\Infrastructure\Form\CheckoutType;
use App\Infrastructure\Form\RefundType;
use App\Infrastructure\Form\Step2Type;
use App\Application\Command\CompletePaymentCommand;
use App\Application\Command\InitializePaymentCommand;
use App\Application\Command\ProcessRefundCommand;
use App\Domain\Exception\PaymentDeclinedException;
use App\Domain\Exception\PaymentErrorException;
use App\Domain\Exception\RefundFailedException;
use App\Domain\Repository\PaymentTransactionRepositoryInterface;
use App\Domain\Service\PaymentGatewayInterface;
use App\Domain\ValueObject\BillingAddress;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly PaymentTransactionRepositoryInterface $transactionRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(Request $request): Response
    {
        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $billingInfo = new BillingAddress(
                firstName: $data['billingFirstName'],
                lastName: $data['billingLastName'],
                address1: $data['billingAddress1'],
                address2: $data['billingAddress2'],
                city: $data['billingCity'],
                state: $data['billingState'],
                postal: $data['billingPostal'],
                country: $data['billingCountry'],
                email: $data['billingEmail'],
                phone: $data['billingPhone'],
            );

            $command = new InitializePaymentCommand(
                $data['amount'],
                $data['currency'],
                $billingInfo,
                $data['isSubscription'] ?? false
            );

            $transactionUuid = $this->handle($command);

            return $this->redirectToRoute('app_pay', ['uuid' => $transactionUuid]);
        }

        return $this->render('payment/checkout.html.twig', [
            'checkoutForm' => $form->createView(),
        ]);
    }

    #[Route('/pay/{uuid}', name: 'app_pay')]
    public function pay(string $uuid): Response
    {
        $transaction = $this->transactionRepository->findByUuid($uuid);
        if (!$transaction) {
            throw $this->createNotFoundException('Transaction not found');
        }

        $redirectUrl = $this->urlGenerator->generate(
            'app_complete',
            ['uuid' => $uuid],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $initializationResult = $this->paymentGateway->initializePayment(
            $transaction,
            $redirectUrl,
            $transaction->getBillingAddressValueObject()
        );

        $step2Form = $this->createForm(Step2Type::class, null, [
            'action' => $initializationResult->formUrl,
        ]);

        return $this->render('payment/step2.html.twig', [
            'step2Form' => $step2Form->createView(),
            'amount' => $transaction->getAmount(),
        ]);
    }

    #[Route('/complete/{uuid}', name: 'app_complete')]
    public function complete(Request $request, string $uuid): Response
    {
        $tokenId = $request->query->get('token-id');
        if (!$tokenId) {
            $this->addFlash('danger', 'Token ID is missing from the gateway response.');
            return $this->redirectToRoute('app_pay', ['uuid' => $uuid]);
        }

        try {
            $transactionId = $this->handle(new CompletePaymentCommand($tokenId, $uuid));
            $this->addFlash('success', 'Payment successful! Transaction ID: ' . $transactionId);
            return $this->redirectToRoute('app_checkout');
        } catch (HandlerFailedException $e) {
            while ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious();
            }

            $message = 'An unexpected error occurred.';
            if ($e instanceof PaymentDeclinedException) {
                $message = 'Payment was declined: ' . $e->getMessage();
            } elseif ($e instanceof PaymentErrorException) {
                $message = 'A payment error occurred: ' . $e->getMessage();
            } elseif ($e) {
                $message .= ': ' . $e->getMessage();
            }

            $this->addFlash('danger', $message);
            return $this->redirectToRoute('app_pay', ['uuid' => $uuid]);
        }
    }

    #[Route('/refund', name: 'app_refund')]
    public function refund(Request $request): Response
    {
        $form = $this->createForm(RefundType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $command = new ProcessRefundCommand($data['transactionId'], $data['refundAmount']);

            try {
                $newTransactionId = $this->handle($command);
                $this->addFlash('success', 'Refund successful! New Transaction ID: ' . $newTransactionId);
            } catch (HandlerFailedException $e) {
                while ($e instanceof HandlerFailedException) {
                    $e = $e->getPrevious();
                }

                if ($e instanceof RefundFailedException) {
                    $this->addFlash('danger', 'Refund failed: ' . $e->getMessage());
                } else {
                    $this->addFlash('danger', 'An unexpected error occurred during the refund process' . ($e ? ': ' . $e->getMessage() : '.'));
                }
            }

            return $this->redirectToRoute('app_refund');
        }

        return $this->render('payment/refund.html.twig', [
            'refundForm' => $form->createView(),
        ]);
    }
}
