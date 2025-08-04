<?php

namespace App\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class BillingAddress
{
    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $firstName;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $lastName;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $address1;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $address2 = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $city;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $state;

    #[ORM\Column(type: Types::STRING, length: 20)]
    public string $postal;

    #[ORM\Column(type: Types::STRING, length: 5)]
    public string $country;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $email;

    #[ORM\Column(type: Types::STRING, length: 30)]
    public string $phone;
}
