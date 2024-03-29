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

    /**
     * @ORM\Column(type="integer")
     */
    private $paymentNumber;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $paymentReceiptDate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $paymentMethod;


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

    public function getPaymentNumber(): ?int
    {
        return $this->paymentNumber;
    }

    public function setPaymentNumber(int $paymentNumber): self
    {
        $this->paymentNumber = $paymentNumber;

        return $this;
    }

    public function getPaymentReceiptDate(): ?\DateTimeInterface
    {
        return $this->paymentReceiptDate;
    }

    public function setPaymentReceiptDate(?\DateTimeInterface $paymentReceiptDate): self
    {
        $this->paymentReceiptDate = $paymentReceiptDate;

        return $this;
    }

    public function getPaymentMethod(): ?int
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?int $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

}
