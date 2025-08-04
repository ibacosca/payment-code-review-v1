<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Subscription;

interface SubscriptionRepositoryInterface
{
    public function save(Subscription $subscription): void;
    public function find(int $id): ?Subscription;
    public function findDueSubscriptions(\DateTimeInterface $since): array;
}
