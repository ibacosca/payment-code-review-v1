<?php

namespace App\Domain\Entity;

use App\Infrastructure\Persistence\Doctrine\Repository\DoctrineSubscriptionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineSubscriptionRepository::class)]
#[ORM\Table(name: 'subscriptions_tbl')]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private string $uuid;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $paymentToken;

    #[ORM\Column(type: Types::FLOAT)]
    private float $amount;

    #[ORM\Column(type: Types::STRING, length: 3)]
    private string $currency;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $nextBillingAt;

    #[ORM\OneToOne(targetEntity: PaymentTransaction::class)]
    #[ORM\JoinColumn(nullable: false)]
    private PaymentTransaction $initialTransaction;

    public function __construct(
        PaymentTransaction $initialTransaction
    ) {
        if (!$initialTransaction->isSubscription() || !$initialTransaction->getUsedToken()) {
            throw new \LogicException('Cannot create a subscription from a non-subscription or incomplete transaction.');
        }

        $this->uuid = Uuid::v4()->toRfc4122();
        $this->initialTransaction = $initialTransaction;
        $this->paymentToken = $initialTransaction->getUsedToken();
        $this->amount = $initialTransaction->getAmount();
        $this->currency = $initialTransaction->getCurrencyCode();
        $this->status = 'active';
        $this->createdAt = new DateTimeImmutable();
        $this->nextBillingAt = (new DateTimeImmutable())->modify('+1 month');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentToken(): string
    {
        return $this->paymentToken;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getInitialTransaction(): PaymentTransaction
    {
        return $this->initialTransaction;
    }

    public function updateNextBillingDate(): void
    {
        // In a real app, this would parse the subscription interval properly.
        // For now, we'll assume a 1-month interval.
        $this->nextBillingAt = (new DateTimeImmutable())->modify('+1 month');
    }

    public function fail(): void
    {
        $this->status = 'failed';
    }
}
