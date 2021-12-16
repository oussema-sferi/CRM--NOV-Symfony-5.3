<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PaymentRepository::class)
 */
class Payment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $value;

    /**
     * @ORM\Column(type="datetime")
     */
    private $paymentDate;

    /**
     * @ORM\ManyToOne(targetEntity=PaymentSchedule::class, inversedBy="payments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $associatedPaymentSchedule;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPaid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getAssociatedPaymentSchedule(): ?PaymentSchedule
    {
        return $this->associatedPaymentSchedule;
    }

    public function setAssociatedPaymentSchedule(?PaymentSchedule $associatedPaymentSchedule): self
    {
        $this->associatedPaymentSchedule = $associatedPaymentSchedule;

        return $this;
    }

    public function getIsPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): self
    {
        $this->isPaid = $isPaid;

        return $this;
    }
}
