<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\Payment as ModelPayment;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ApiResource()]
class Payment extends ModelPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
