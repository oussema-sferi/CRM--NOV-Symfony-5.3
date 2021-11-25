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
}
