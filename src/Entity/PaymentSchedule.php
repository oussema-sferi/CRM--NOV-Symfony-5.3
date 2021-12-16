<?php

namespace App\Entity;

use App\Repository\PaymentScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PaymentScheduleRepository::class)
 */
class PaymentSchedule
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Project::class, inversedBy="paymentSchedule", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="paymentSchedules")
     * @ORM\JoinColumn(nullable=false)
     */
    private $whoGenerateIt;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="paymentSchedules")
     * @ORM\JoinColumn(nullable=false)
     */
    private $client;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDeleted;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="deletedPaymentSchedules")
     */
    private $whoDeletedIt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="associatedPaymentSchedule")
     */
    private $payments;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getWhoGenerateIt(): ?User
    {
        return $this->whoGenerateIt;
    }

    public function setWhoGenerateIt(?User $whoGenerateIt): self
    {
        $this->whoGenerateIt = $whoGenerateIt;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getWhoDeletedIt(): ?User
    {
        return $this->whoDeletedIt;
    }

    public function setWhoDeletedIt(?User $whoDeletedIt): self
    {
        $this->whoDeletedIt = $whoDeletedIt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setAssociatedPaymentSchedule($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getAssociatedPaymentSchedule() === $this) {
                $payment->setAssociatedPaymentSchedule(null);
            }
        }

        return $this;
    }
}
