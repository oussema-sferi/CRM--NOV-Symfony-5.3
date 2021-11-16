<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=AppointmentRepository::class)
 */
class Appointment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     */
    private $start;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     */
    private $end;


    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $appointmentNotes;


    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="appointment")
     * @ORM\JoinColumn(nullable=true)
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="appointments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $isDone;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $doneAt;

    /**
     * @ORM\ManyToOne(targetEntity=Call::class, inversedBy="appointments")
     * @ORM\JoinColumn(nullable=true)
     */
    private $appointmentCall;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDeleted;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @ORM\ManyToOne(targetEntity=EventType::class, inversedBy="appointments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $eventType;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="fixedAppointments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $appointmentFixer;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="deletedAppointments")
     */
    private $whoDeletedIt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $postAppointmentNotes;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPostponed;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $postponedAt;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(\DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }


    public function getAppointmentNotes(): ?string
    {
        return $this->appointmentNotes;
    }

    public function setAppointmentNotes(string $appointmentNotes): self
    {
        $this->appointmentNotes = $appointmentNotes;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
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

    /**
     * @return mixed
     */
    public function getIsDone()
    {
        return $this->isDone;
    }

    /**
     * @param mixed $isDone
     */
    public function setIsDone($isDone): void
    {
        $this->isDone = $isDone;
    }



    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getDoneAt()
    {
        return $this->doneAt;
    }

    /**
     * @param mixed $doneAt
     */
    public function setDoneAt($doneAt): void
    {
        $this->doneAt = $doneAt;
    }

    public function getAppointmentCall(): ?Call
    {
        return $this->appointmentCall;
    }

    public function setAppointmentCall(?Call $appointmentCall): self
    {
        $this->appointmentCall = $appointmentCall;

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

    public function getEventType(): ?EventType
    {
        return $this->eventType;
    }

    public function setEventType(?EventType $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getAppointmentFixer(): ?User
    {
        return $this->appointmentFixer;
    }

    public function setAppointmentFixer(?User $appointmentFixer): self
    {
        $this->appointmentFixer = $appointmentFixer;

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

    public function getPostAppointmentNotes(): ?string
    {
        return $this->postAppointmentNotes;
    }

    public function setPostAppointmentNotes(?string $postAppointmentNotes): self
    {
        $this->postAppointmentNotes = $postAppointmentNotes;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getIsPostponed(): ?bool
    {
        return $this->isPostponed;
    }

    public function setIsPostponed(bool $isPostponed): self
    {
        $this->isPostponed = $isPostponed;

        return $this;
    }

    public function getPostponedAt(): ?\DateTimeInterface
    {
        return $this->postponedAt;
    }

    public function setPostponedAt(?\DateTimeInterface $postponedAt): self
    {
        $this->postponedAt = $postponedAt;

        return $this;
    }

}
