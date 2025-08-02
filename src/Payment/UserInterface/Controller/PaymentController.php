<?php

namespace App\Payment\UserInterface\Controller;

use App\Form\CheckoutType;
use App\Form\RefundType;
use App\Form\Step2Type;
use App\Payment\Application\Command\CompletePaymentCommand;
use App\Payment\Application\Command\InitializePaymentCommand;
use App\Payment\Application\Command\ProcessRefundCommand;
use App\Payment\Application\DTO\BillingInfoDTO;
use App\Payment\Domain\Exception\PaymentDeclinedException;
use App\Payment\Domain\Exception\PaymentErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(Request $request): Response
    {
        if ($tokenId = $request->query->get('token-id')) {
            try {
                $transactionId = $this->handle(new CompletePaymentCommand($tokenId));
                $this->addFlash('success', 'Payment successful! Transaction ID: ' . $transactionId);
            } catch (PaymentDeclinedException $e) {
                $this->addFlash('warning', 'Payment was declined: ' . $e->getMessage());
            } catch (PaymentErrorException $e) {
                $this->addFlash('danger', 'A payment error occurred: ' . $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('danger', 'An unexpected error occurred: ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_checkout');
        }

        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $billingInfo = new BillingInfoDTO(
                firstName: $data['billingFirstName'],
                lastName: $data['billingLastName'],
                postal: $data['billingPostal'],
                country: $data['billingCountry'],
                address1: $data['billingAddress1'],
                address2: $data['billingAddress2'],
                city: $data['billingCity'],
                state: $data['billingState'],
                email: $data['billingEmail'],
                phone: $data['billingPhone'],
            );
            
            $redirectUrl = $this->generateUrl('app_checkout', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $command = new InitializePaymentCommand($data['amount'], $data['currency'], $billingInfo, $redirectUrl);

            try {
                $formUrl = $this->handle($command);
                $step2Form = $this->createForm(Step2Type::class, null, ['action' => $formUrl]);

                return $this->render('payment/step2.html.twig', [
                    'step2Form' => $step2Form->createView(),
                    'amount' => $data['amount'],
                ]);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Failed to initialize payment: ' . $e->getMessage());
            }
        }

        return $this->render('payment/checkout.html.twig', [
            'checkoutForm' => $form->createView(),
        ]);
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
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Refund failed: ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_refund');
        }

        return $this->render('payment/refund.html.twig', [
            'refundForm' => $form->createView(),
        ]);
    }
}
