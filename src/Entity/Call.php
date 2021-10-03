<?php

namespace App\Entity;

use App\Repository\CallRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CallRepository::class)
 * @ORM\Table(name="`call`")
 */
class Call
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $generalStatus;

    /**
     * @ORM\Column(type="integer")
     */
    private $statusDetails;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $callNotes;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $callIfAppointmentNotes;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="calls")
     * @ORM\JoinColumn(nullable=false)
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="calls")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity=Appointment::class, mappedBy="appointmentCall")
     */
    private $appointments;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDeleted;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletionDate;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
    }


    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }


    public function getId(): ?int
    {
        return $this->id;
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

    public function getGeneralStatus(): ?int
    {
        return $this->generalStatus;
    }

    public function setGeneralStatus(int $generalStatus): self
    {
        $this->generalStatus = $generalStatus;

        return $this;
    }

    public function getCallNotes(): ?string
    {
        return $this->callNotes;
    }

    public function setCallNotes(?string $callNotes): self
    {
        $this->callNotes = $callNotes;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStatusDetails(): ?int
    {
        return $this->statusDetails;
    }

    public function setStatusDetails(int $statusDetails): self
    {
        $this->statusDetails = $statusDetails;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCallIfAppointmentNotes()
    {
        return $this->callIfAppointmentNotes;
    }

    /**
     * @param mixed $callIfAppointmentNotes
     */
    public function setCallIfAppointmentNotes($callIfAppointmentNotes): void
    {
        $this->callIfAppointmentNotes = $callIfAppointmentNotes;
    }

    /**
     * @return Collection|Appointment[]
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments[] = $appointment;
            $appointment->setAppointmentCall($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getAppointmentCall() === $this) {
                $appointment->setAppointmentCall(null);
            }
        }

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

    public function getDeletionDate(): ?\DateTimeInterface
    {
        return $this->deletionDate;
    }

    public function setDeletionDate(?\DateTimeInterface $deletionDate): self
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

}
