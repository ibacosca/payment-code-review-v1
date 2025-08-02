<?php

namespace App\Payment\Domain\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: "App\Payment\Infrastructure\Persistence\Doctrine\Repository\DoctrinePaymentTransactionRepository")]
#[ORM\Table(name: "payment_transactions_tbl")]
class PaymentTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private string $uuid;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $transactionId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, name: "_used_token")]
    private ?string $usedToken = null;

    #[ORM\Column(type: Types::FLOAT)]
    private float $amount;

    #[ORM\Column(type: Types::STRING, length: 50, name: "currency_code")]
    private string $currencyCode;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $paymentStatus;

    #[ORM\Column(type: Types::STRING, length: 4, nullable: true)]
    private ?string $last4Digits = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    private function __construct(float $amount, string $currencyCode)
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->amount = $amount;
        $this->currencyCode = $currencyCode;
        $this->paymentStatus = 'initialized';
        $this->createdAt = new DateTimeImmutable();
    }

    public static function initialize(float $amount, string $currencyCode): self
    {
        return new self($amount, $currencyCode);
    }

    public function complete(string $transactionId, string $last4Digits, string $usedToken): void
    {
        $this->transactionId = $transactionId;
        $this->last4Digits = $last4Digits;
        $this->usedToken = $usedToken;
        $this->paymentStatus = 'completed';
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
}
