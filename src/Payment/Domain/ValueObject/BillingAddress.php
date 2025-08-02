<?php

namespace App\Payment\Domain\ValueObject;

class BillingAddress
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $postal,
        public readonly string $country,
        public readonly ?string $address1 = null,
        public readonly ?string $address2 = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
    ) {
    }
}
