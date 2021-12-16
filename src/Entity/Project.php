<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 */
class Project
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="projects")
     * @ORM\JoinColumn(nullable=false)
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="projects")
     * @ORM\JoinColumn(nullable=false)
     */
    private $projectMakerUser;

    /**
     * @ORM\ManyToOne(targetEntity=Equipment::class, inversedBy="projects")
     * @ORM\JoinColumn(nullable=false)
     */
    private $equipment;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $monthlyPayment;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $numberOfMonthlyPayments;

    /**
     * @ORM\Column(type="boolean")
     */
    private $rachat;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $projectNotes;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalHT;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $reportMensualite;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $shipmentStatus;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $shipmentStatusDate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $shipmentNotes;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $cni;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $rib;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $declaration2035;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $declaration2042;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $bilanComptable;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $partenariat;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDeleted;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="deletedProjects")
     */
    private $whoDeletedIt;

    /**
     * @ORM\OneToOne(targetEntity=PaymentSchedule::class, mappedBy="project", cascade={"persist", "remove"})
     */
    private $paymentSchedule;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProjectMakerUser(): ?User
    {
        return $this->projectMakerUser;
    }

    public function setProjectMakerUser(?User $projectMakerUser): self
    {
        $this->projectMakerUser = $projectMakerUser;

        return $this;
    }

    public function getEquipment(): ?Equipment
    {
        return $this->equipment;
    }

    public function setEquipment(?Equipment $equipment): self
    {
        $this->equipment = $equipment;

        return $this;
    }

    public function getMonthlyPayment(): ?string
    {
        return $this->monthlyPayment;
    }

    public function setMonthlyPayment(?string $monthlyPayment): self
    {
        $this->monthlyPayment = $monthlyPayment;

        return $this;
    }

    public function getNumberOfMonthlyPayments(): ?int
    {
        return $this->numberOfMonthlyPayments;
    }

    public function setNumberOfMonthlyPayments(?int $numberOfMonthlyPayments): self
    {
        $this->numberOfMonthlyPayments = $numberOfMonthlyPayments;

        return $this;
    }

    public function getRachat(): ?bool
    {
        return $this->rachat;
    }

    public function setRachat(bool $rachat): self
    {
        $this->rachat = $rachat;

        return $this;
    }

    public function getProjectNotes(): ?string
    {
        return $this->projectNotes;
    }

    public function setProjectNotes(?string $projectNotes): self
    {
        $this->projectNotes = $projectNotes;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getTotalHT(): ?float
    {
        return $this->totalHT;
    }

    public function setTotalHT(?float $totalHT): self
    {
        $this->totalHT = $totalHT;

        return $this;
    }

    public function getReportMensualite(): ?int
    {
        return $this->reportMensualite;
    }

    public function setReportMensualite(?int $reportMensualite): self
    {
        $this->reportMensualite = $reportMensualite;

        return $this;
    }

    public function getShipmentStatus(): ?int
    {
        return $this->shipmentStatus;
    }

    public function setShipmentStatus(?int $shipmentStatus): self
    {
        $this->shipmentStatus = $shipmentStatus;

        return $this;
    }

    public function getShipmentStatusDate(): ?\DateTimeInterface
    {
        return $this->shipmentStatusDate;
    }

    public function setShipmentStatusDate(?\DateTimeInterface $shipmentStatusDate): self
    {
        $this->shipmentStatusDate = $shipmentStatusDate;

        return $this;
    }

    public function getShipmentNotes(): ?string
    {
        return $this->shipmentNotes;
    }

    public function setShipmentNotes(?string $shipmentNotes): self
    {
        $this->shipmentNotes = $shipmentNotes;

        return $this;
    }

    public function getCni(): ?string
    {
        return $this->cni;
    }

    public function setCni(?string $cni): self
    {
        $this->cni = $cni;

        return $this;
    }

    public function getRib(): ?string
    {
        return $this->rib;
    }

    public function setRib(?string $rib): self
    {
        $this->rib = $rib;

        return $this;
    }

    public function getDeclaration2035(): ?string
    {
        return $this->declaration2035;
    }

    public function setDeclaration2035(?string $declaration2035): self
    {
        $this->declaration2035 = $declaration2035;

        return $this;
    }

    public function getDeclaration2042(): ?string
    {
        return $this->declaration2042;
    }

    public function setDeclaration2042(?string $declaration2042): self
    {
        $this->declaration2042 = $declaration2042;

        return $this;
    }

    public function getBilanComptable(): ?string
    {
        return $this->bilanComptable;
    }

    public function setBilanComptable(?string $bilanComptable): self
    {
        $this->bilanComptable = $bilanComptable;

        return $this;
    }

    public function getPartenariat(): ?string
    {
        return $this->partenariat;
    }

    public function setPartenariat(?string $partenariat): self
    {
        $this->partenariat = $partenariat;

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

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

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

    public function getPaymentSchedule(): ?PaymentSchedule
    {
        return $this->paymentSchedule;
    }

    public function setPaymentSchedule(PaymentSchedule $paymentSchedule): self
    {
        // set the owning side of the relation if necessary
        if ($paymentSchedule->getProject() !== $this) {
            $paymentSchedule->setProject($this);
        }

        $this->paymentSchedule = $paymentSchedule;

        return $this;
    }
}
