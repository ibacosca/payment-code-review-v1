<?php

namespace App\Domain\Entity;

use App\Domain\ValueObject\BillingAddress as BillingAddressValueObject;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: "App\Infrastructure\Persistence\Doctrine\Repository\DoctrinePaymentTransactionRepository")]
#[ORM\Table(name: "payment_transactions_tbl")]
class PaymentTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['transaction:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    #[Groups(['transaction:read'])]
    private string $uuid;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['transaction:read'])]
    private ?string $transactionId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, name: "_used_token")]
    private ?string $usedToken = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['transaction:read'])]
    private float $amount;

    #[ORM\Column(type: Types::STRING, length: 50, name: "currency_code")]
    #[Groups(['transaction:read'])]
    private string $currencyCode;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Groups(['transaction:read'])]
    private string $paymentStatus;

    #[ORM\Column(type: Types::STRING, length: 4, nullable: true)]
    #[Groups(['transaction:read'])]
    private ?string $last4Digits = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['transaction:read'])]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => false])]
    #[Groups(['transaction:read'])]
    private bool $isSubscription;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Groups(['transaction:read'])]
    private ?string $subscriptionInterval = null;

    #[ORM\Embedded(class: BillingAddress::class)]
    private BillingAddress $billingAddress;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'refunds')]
    private ?self $parentTransaction = null;

    #[ORM\OneToMany(mappedBy: 'parentTransaction', targetEntity: self::class)]
    private Collection $refunds;

    private function __construct(float $amount, string $currencyCode, BillingAddressValueObject $billingInfo)
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->amount = $amount;
        $this->currencyCode = $currencyCode;
        $this->paymentStatus = 'initialized';
        $this->createdAt = new DateTimeImmutable();
        $this->refunds = new ArrayCollection();
        $this->isSubscription = false;
        $this->billingAddress = new BillingAddress();
        $this->billingAddress->firstName = $billingInfo->firstName;
        $this->billingAddress->lastName = $billingInfo->lastName;
        $this->billingAddress->address1 = $billingInfo->address1;
        $this->billingAddress->address2 = $billingInfo->address2;
        $this->billingAddress->city = $billingInfo->city;
        $this->billingAddress->state = $billingInfo->state;
        $this->billingAddress->postal = $billingInfo->postal;
        $this->billingAddress->country = $billingInfo->country;
        $this->billingAddress->email = $billingInfo->email;
        $this->billingAddress->phone = $billingInfo->phone;
    }

    public static function initialize(float $amount, string $currencyCode, BillingAddressValueObject $billingInfo): self
    {
        return new self($amount, $currencyCode, $billingInfo);
    }

    public static function createRefundFor(self $originalTransaction, float $refundAmount, string $refundTransactionId): self
    {
        if ($refundAmount > $originalTransaction->getAmount()) {
            throw new \LogicException('Refund amount cannot be greater than the original transaction amount.');
        }

        $refund = new self(-$refundAmount, $originalTransaction->getCurrencyCode(), $originalTransaction->getBillingAddressValueObject());
        $refund->paymentStatus = 'refunded';
        $refund->parentTransaction = $originalTransaction;
        $refund->transactionId = $refundTransactionId;
        $refund->last4Digits = $originalTransaction->getLast4Digits();

        return $refund;
    }

    public function complete(string $transactionId, string $last4Digits, string $usedToken): void
    {
        $this->transactionId = $transactionId;
        $this->last4Digits = $last4Digits;
        $this->usedToken = $usedToken;
        $this->paymentStatus = 'completed';
    }

    public function startSubscription(string $interval): void
    {
        $this->isSubscription = true;
        $this->subscriptionInterval = $interval;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getUsedToken(): ?string
    {
        return $this->usedToken;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getLast4Digits(): ?string
    {
        return $this->last4Digits;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isSubscription(): bool
    {
        return $this->isSubscription;
    }

    public function getSubscriptionInterval(): ?string
    {
        return $this->subscriptionInterval;
    }

    public function getBillingAddress(): BillingAddress
    {
        return $this->billingAddress;
    }
    
    public function getBillingAddressValueObject(): BillingAddressValueObject
    {
        return new BillingAddressValueObject(
            $this->billingAddress->firstName,
            $this->billingAddress->lastName,
            $this->billingAddress->address1,
            $this->billingAddress->address2,
            $this->billingAddress->city,
            $this->billingAddress->state,
            $this->billingAddress->postal,
            $this->billingAddress->country,
            $this->billingAddress->email,
            $this->billingAddress->phone,
        );
    }

    public function getRefundableAmount(): float
    {
        if ($this->paymentStatus !== 'completed') {
            return 0.0;
        }

        $refundedAmount = 0.0;
        foreach ($this->refunds as $refund) {
            $refundedAmount += abs($refund->getAmount());
        }

        return $this->amount - $refundedAmount;
    }
}
